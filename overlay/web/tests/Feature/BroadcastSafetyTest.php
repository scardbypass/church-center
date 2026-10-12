<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BroadcastSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function setupChurch(User $user): int
    {
        $id = DB::table('churches')->insertGetId([
            'name' => 'Gereja Test', 'slug' => 'gereja-test', 'timezone' => 'Asia/Jakarta',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_memberships')->insert([
            'church_id' => $id, 'user_id' => $user->id, 'role' => 'owner',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_licenses')->insert([
            'church_id' => $id, 'duration_months' => 1, 'status' => 'active',
            'starts_at' => now(), 'expires_at' => now()->addMonth(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_broadcast_only_queues_consented_contacts(): void
    {
        $user = User::factory()->create();
        $church = $this->setupChurch($user);
        foreach ([['+6281234567890', now()], ['+6281234567891', null]] as [$phone, $consent]) {
            DB::table('church_contacts')->insert([
                'church_id' => $church, 'name' => 'Test', 'phone_e164' => $phone,
                'consent_at' => $consent, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->actingAs($user)->post("/churches/{$church}/broadcast", [
            'message' => 'Informasi ibadah hari Minggu.',
        ])->assertRedirect();
        $this->assertDatabaseCount('church_automation_jobs', 1);
        $this->assertDatabaseHas('church_automation_jobs', [
            'church_id' => $church, 'target' => '6281234567890@s.whatsapp.net',
            'status' => 'queued', 'kind' => 'broadcast',
        ]);
    }

    public function test_nonmember_cannot_queue_broadcast(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $church = $this->setupChurch($owner);
        $this->actingAs($stranger)->post("/churches/{$church}/broadcast", [
            'message' => 'Unauthorized message',
        ])->assertForbidden();
    }

    public function test_guest_cannot_access_reports(): void
    {
        $this->get('/admin/reports')->assertRedirect('/login');
    }
}
