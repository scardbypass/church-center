<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChurchAutomationSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function church(User $user): int
    {
        $id = DB::table('churches')->insertGetId([
            'name' => 'Test', 'slug' => 'test-security', 'timezone' => 'Asia/Jakarta',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_memberships')->insert([
            'church_id' => $id, 'user_id' => $user->id, 'role' => 'owner',
            'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_scheduled_messages_reject_unconsented_recipients(): void
    {
        $user = User::factory()->create();
        $church = $this->church($user);
        $this->actingAs($user)->post("/churches/{$church}/automations", [
            'kind' => 'reminder', 'target' => '6281234567890@s.whatsapp.net',
            'message' => 'Jadwal ibadah', 'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertStatus(422);
        $this->assertDatabaseCount('church_automation_jobs', 0);
    }

    public function test_group_recipients_cannot_be_scheduled(): void
    {
        $user = User::factory()->create();
        $church = $this->church($user);
        $this->actingAs($user)->post("/churches/{$church}/automations", [
            'kind' => 'reminder', 'target' => '12345-678@g.us',
            'message' => 'Jadwal ibadah', 'scheduled_at' => now()->addDay()->toDateTimeString(),
        ])->assertSessionHasErrors('target');
        $this->assertDatabaseCount('church_automation_jobs', 0);
    }
}
