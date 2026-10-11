<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChurchContactsTest extends TestCase
{
    use RefreshDatabase;

    private function churchFor(User $user, string $name): int
    {
        $id = DB::table('churches')->insertGetId([
            'name' => $name,
            'slug' => strtolower($name),
            'timezone' => 'Asia/Jakarta',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('church_memberships')->insert([
            'church_id' => $id,
            'user_id' => $user->id,
            'role' => 'owner',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_guest_cannot_view_contacts(): void
    {
        $this->get('/churches/1/contacts')->assertRedirect('/login');
    }

    public function test_members_can_add_contacts_and_other_church_cannot_read_or_delete_them(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $a = $this->churchFor($alice, 'Alpha');
        $b = $this->churchFor($bob, 'Beta');

        $this->actingAs($alice)->post("/churches/{$a}/contacts", [
            'name' => 'Maria',
            'phone' => '081234567890',
            'consent' => '1',
        ])->assertRedirect();

        $contact = DB::table('church_contacts')->where('church_id', $a)->first();
        $this->assertSame('+6281234567890', $contact->phone_e164);
        $this->assertNotNull($contact->consent_at);

        $this->actingAs($bob)->get("/churches/{$a}/contacts")->assertForbidden();
        $this->actingAs($bob)->delete("/churches/{$a}/contacts/{$contact->id}")->assertForbidden();
        $this->assertDatabaseHas('church_contacts', ['id' => $contact->id, 'church_id' => $a]);

        $this->actingAs($bob)->get("/churches/{$b}/contacts")->assertOk();
    }

    public function test_duplicate_contact_is_rejected_within_same_church(): void
    {
        $user = User::factory()->create();
        $church = $this->churchFor($user, 'Gamma');
        $this->actingAs($user)->post("/churches/{$church}/contacts", ['name' => 'Ana', 'phone' => '081234567890'])->assertRedirect();
        $this->actingAs($user)->post("/churches/{$church}/contacts", ['name' => 'Ana Dua', 'phone' => '+6281234567890'])->assertSessionHasErrors('phone');
        $this->assertSame(1, DB::table('church_contacts')->where('church_id', $church)->count());
    }
}
