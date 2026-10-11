<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_connection(): void
    {
        $this->get('/churches/1/whatsapp')->assertRedirect('/login');
    }

    public function test_other_church_member_cannot_view_or_connect(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/churches/999/whatsapp')->assertForbidden();
        $this->post('/churches/999/whatsapp/connect')->assertForbidden();
    }

    public function test_member_sees_worker_status_and_private_qr_image(): void
    {
        $user = User::factory()->create();
        $churchId = DB::table('churches')->insertGetId([
            'name' => 'Test Church', 'slug' => 'test-church',
            'timezone' => 'Asia/Jakarta', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_memberships')->insert([
            'church_id' => $churchId, 'user_id' => $user->id,
            'role' => 'owner', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        config()->set('services.church_worker.token', str_repeat('a', 40));
        config()->set('services.church_worker.url', 'http://127.0.0.1:31181');
        Http::fake([
            'http://127.0.0.1:31181/churches/'.$churchId.'/status' =>
                Http::response(['status' => 'qr_ready', 'qr_ready' => true]),
            'http://127.0.0.1:31181/churches/'.$churchId.'/qr' =>
                Http::response(['image' => 'data:image/png;base64,'.base64_encode('fakepng')]),
        ]);
        $this->actingAs($user)->get('/churches/'.$churchId.'/whatsapp')
            ->assertOk()->assertSee('data:image/png;base64,', false)
            ->assertHeader('Cache-Control', 'private, no-store');
    }
}
