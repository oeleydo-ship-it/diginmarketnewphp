<?php

namespace Tests\Feature;

use App\Enums\SellerStatus;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EditorImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $admin;
    }

    private function approvedSeller(): User
    {
        $role = Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']);
        $seller = User::factory()->create();
        $seller->roles()->attach($role);

        SellerProfile::create([
            'user_id' => $seller->id,
            'display_name' => 'Editor Studio',
            'username' => 'editor-studio-'.$seller->id,
            'country' => 'AE',
            'biography' => 'Approved seller profile for editor uploads.',
            'status' => SellerStatus::Approved,
        ]);

        return $seller;
    }

    public function test_guests_and_customers_cannot_upload_editor_images(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('editor.png');

        $this->postJson(route('editor.images.store'), ['image' => $file])->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->postJson(route('editor.images.store'), ['image' => $file])
            ->assertForbidden();
    }

    public function test_approved_sellers_and_admins_can_upload_editor_images(): void
    {
        Storage::fake('public');

        $sellerResponse = $this->actingAs($this->approvedSeller())
            ->postJson(route('editor.images.store'), ['image' => UploadedFile::fake()->image('seller.png', 1600, 900)]);

        $sellerResponse->assertCreated()->assertJsonPath('url', fn ($url) => is_string($url) && str_contains($url, '/storage/editor/'));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', parse_url($sellerResponse->json('url'), PHP_URL_PATH)));

        $adminResponse = $this->actingAs($this->admin())
            ->postJson(route('editor.images.store'), ['image' => UploadedFile::fake()->image('admin.png', 1200, 800)]);

        $adminResponse->assertCreated()->assertJsonPath('url', fn ($url) => is_string($url) && str_contains($url, '/storage/editor/'));
    }

    public function test_upload_requires_a_supported_image_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->approvedSeller())
            ->postJson(route('editor.images.store'), ['image' => UploadedFile::fake()->create('not-image.pdf', 50, 'application/pdf')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->actingAs($this->approvedSeller())
            ->postJson(route('editor.images.store'), ['image' => UploadedFile::fake()->image('huge.png')->size(6000)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }
}
