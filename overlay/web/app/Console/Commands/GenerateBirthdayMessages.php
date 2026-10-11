<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateBirthdayMessages extends Command
{
    protected $signature = 'church:birthdays';
    protected $description = 'Queue opt-in birthday greetings once per church-local calendar day';

    public function handle(): int
    {
        DB::table('churches')->where('active', true)->orderBy('id')->chunkById(100, function ($churches) {
            foreach ($churches as $church) {
                $licensed = DB::table('church_licenses')->where('church_id', $church->id)
                    ->where('status', 'active')->where('expires_at', '>', now())->exists();
                if (!$licensed) continue;
                $today = now($church->timezone)->toDateString();
                $setting = DB::table('church_settings')->where('church_id', $church->id)
                    ->where('key', 'birthday_message')->first();
                $template = $setting ? json_decode($setting->value, true) : null;
                if (!is_string($template) || trim($template) === '') continue;
                $mmdd = substr($today, 5);
                DB::table('church_contacts')->where('church_id', $church->id)
                    ->whereNotNull('consent_at')->whereNotNull('birthday')
                    ->whereRaw("DATE_FORMAT(birthday, '%m-%d') = ?", [$mmdd])
                    ->orderBy('id')->chunkById(100, function ($contacts) use ($church, $today, $template) {
                        foreach ($contacts as $contact) {
                            $message = str_replace('{name}', $contact->name, $template);
                            if (mb_strlen($message) > 5000) continue;
                            DB::table('church_automation_jobs')->insertOrIgnore([
                                'church_id' => $church->id, 'kind' => 'reminder',
                                'target' => ltrim($contact->phone_e164, '+').'@s.whatsapp.net',
                                'message' => $message, 'scheduled_at' => now(),
                                'status' => 'queued', 'idempotency_key' => 'birthday:'.$church->id.':'.$contact->id.':'.$today,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    });
            }
        });
        return self::SUCCESS;
    }
}
