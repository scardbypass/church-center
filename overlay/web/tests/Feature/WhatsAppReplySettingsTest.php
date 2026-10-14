<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppReplySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function church(User $user): int
    {
        $churchId = DB::table('churches')->insertGetId([
            'name' => 'Church FAQ', 'slug' => 'church-faq',
            'timezone' => 'Asia/Jakarta', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('church_memberships')->insert([
            'church_id' => $churchId, 'user_id' => $user->id,
            'role' => 'owner', 'active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $churchId;
    }

    public function test_faq_is_saved_per_church_and_sent_to_worker(): void
    {
        $user = User::factory()->create();
        $id = $this->church($user);
        config()->set('services.church_worker.url', 'http://127.0.0.1:31181');
        config()->set('services.church_worker.token', str_repeat('x', 40));
        Http::fake(['http://127.0.0.1:31181/*' => Http::response(['status' => 'configured'])]);
        $this->actingAs($user)->post("/churches/{$id}/whatsapp/replies", [
            'welcome_message' => 'Selamat datang',
            'faq' => "jadwal|Ibadah Minggu\nalamat|Jalan Gereja",
        ])->assertRedirect();
        $this->assertDatabaseHas('church_settings', [
            'church_id' => $id, 'key' => 'whatsapp_replies',
        ]);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), "/churches/{$id}/replies")
            && $request['faq']['jadwal'] === 'Ibadah Minggu');
    }

    public function test_non_member_cannot_change_faq(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $id = $this->church($owner);
        $this->actingAs($other)->post("/churches/{$id}/whatsapp/replies", [
            'welcome_message' => 'Unauthorized', 'faq' => 'jadwal|Wrong',
        ])->assertForbidden();
    }

    public function test_invalid_keyword_is_rejected(): void
    {
        $owner = User::factory()->create();
        $id = $this->church($owner);
        $this->actingAs($owner)->post("/churches/{$id}/whatsapp/replies", [
            'welcome_message' => 'Selamat datang',
            'faq' => 'two words|Invalid keyword',
        ])->assertSessionHasErrors('faq');
    }
}
