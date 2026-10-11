<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DispatchChurchMessages extends Command
{
    protected $signature = 'church:dispatch {--limit=20}';
    protected $description = 'Dispatch due approved church WhatsApp messages with conservative delivery semantics';

    public function handle(): int
    {
        $token = config('services.church_worker.token');
        $url = config('services.church_worker.url');
        if (!is_string($token) || strlen($token) < 32 || !preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~', (string) $url)) {
            $this->error('Worker configuration invalid');
            return self::FAILURE;
        }
        $limit = max(1, min(100, (int) $this->option('limit')));
        $ids = DB::table('church_automation_jobs')->where('status', 'queued')
            ->where('scheduled_at', '<=', now())->orderBy('scheduled_at')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            // Conservative global pacing for the local worker.
            usleep(1500000);
            $job = DB::transaction(function () use ($id) {
                $job = DB::table('church_automation_jobs')->where('id', $id)->lockForUpdate()->first();
                if (!$job || $job->status !== 'queued' || $job->scheduled_at > now()) return null;
                $church = DB::table('churches')->where('id', $job->church_id)->first();
                if (!$church || !$church->active) return null;
                $licensed = DB::table('church_licenses')->where('church_id', $job->church_id)
                    ->where('status', 'active')->where('expires_at', '>', now())->exists();
                if (!$licensed) return null;
                if (preg_match('/^([0-9]{6,16})@s\.whatsapp\.net$/', $job->target, $m)) {
                    $consent = DB::table('church_contacts')->where('church_id', $job->church_id)
                        ->where('phone_e164', '+'.$m[1])->whereNotNull('consent_at')->exists();
                    if (!$consent) {
                        DB::table('church_automation_jobs')->where('id', $id)->update([
                            'status' => 'blocked', 'last_error' => 'Contact consent missing', 'updated_at' => now(),
                        ]);
                        return null;
                    }
                } else {
                    DB::table('church_automation_jobs')->where('id', $id)->update([
                        'status' => 'blocked', 'last_error' => 'Group or invalid recipient is not approved for automated sends', 'updated_at' => now(),
                    ]);
                    return null;
                }
                DB::table('church_automation_jobs')->where('id', $id)->update([
                    'status' => 'sending', 'attempts' => $job->attempts + 1, 'updated_at' => now(),
                ]);
                return $job;
            }, 3);
            if (!$job) continue;
            try {
                $response = Http::withToken($token)->acceptJson()->timeout(20)
                    ->post($url.'/churches/'.$job->church_id.'/send', [
                        'to' => $job->target, 'text' => $job->message,
                    ]);
                DB::table('church_automation_jobs')->where('id', $id)->where('status', 'sending')->update([
                    'status' => $response->successful() ? 'sent' : 'failed',
                    'sent_at' => $response->successful() ? now() : null,
                    'last_error' => $response->successful() ? null : 'Worker rejected send (HTTP '.$response->status().')',
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Unknown delivery state: NEVER retry automatically (could duplicate a message).
                DB::table('church_automation_jobs')->where('id', $id)->where('status', 'sending')->update([
                    'status' => 'unknown', 'last_error' => 'Delivery outcome unknown; manual review required',
                    'updated_at' => now(),
                ]);
                $this->warn('Message '.$id.' requires manual review');
            }
        }
        return self::SUCCESS;
    }
}
