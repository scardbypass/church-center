<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateRecurringChurchMessages extends Command
{
    protected $signature = 'church:generate-recurring';
    protected $description = 'Queue enabled daily IH messages once per church-local day';

    public function handle(): int
    {
        DB::table('church_recurring_messages')->where('enabled', true)->orderBy('id')
            ->chunkById(100, function ($rules) {
                foreach ($rules as $rule) {
                    $church = DB::table('churches')->where('id', $rule->church_id)->where('active', true)->first();
                    if (!$church) continue;
                    $licensed = DB::table('church_licenses')->where('church_id', $rule->church_id)
                        ->where('status', 'active')->where('expires_at', '>', now())->exists();
                    if (!$licensed) continue;
                    try {
                        $now = Carbon::now($rule->timezone);
                        $scheduled = Carbon::parse($now->toDateString().' '.$rule->local_time, $rule->timezone);
                        if ($scheduled->isFuture()) continue;
                        $key = 'daily:'.$rule->id.':'.$now->toDateString();
                        DB::table('church_automation_jobs')->insertOrIgnore([
                            'church_id' => $rule->church_id, 'kind' => $rule->kind,
                            'target' => $rule->target, 'message' => $rule->message,
                            'scheduled_at' => $scheduled->utc()->toDateTimeString(),
                            'status' => 'queued', 'idempotency_key' => $key,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        $this->warn('Skipped recurring rule '.$rule->id);
                    }
                }
            });
        return self::SUCCESS;
    }
}
