<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncChurchWhatsAppReplies extends Command
{
    protected $signature = 'church:sync-replies';
    protected $description = 'Restore tenant WhatsApp FAQ settings after worker restarts';

    public function handle(): int
    {
        $url = config('services.church_worker.url');
        $token = config('services.church_worker.token');
        if (!is_string($url) || !preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~', $url)
            || !is_string($token) || strlen($token) < 32) {
            $this->error('Worker configuration invalid');
            return self::FAILURE;
        }
        $failed = 0;
        DB::table('church_settings')->where('key', 'whatsapp_replies')
            ->orderBy('id')->chunkById(100, function ($settings) use ($url, $token, &$failed) {
                foreach ($settings as $row) {
                    $config = json_decode($row->value, true);
                    if (!is_array($config) || !isset($config['welcome_message'], $config['faq'])) continue;
                    try {
                        $response = Http::withToken($token)->timeout(5)
                            ->post($url.'/churches/'.$row->church_id.'/replies', $config);
                        if (!$response->successful()) $failed++;
                    } catch (\Throwable $e) {
                        $failed++;
                    }
                }
            });
        $this->info('FAQ synchronization completed. Failures: '.$failed);
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
