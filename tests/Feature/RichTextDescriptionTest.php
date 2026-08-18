<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RichTextDescriptionTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): User
    {
        $role = Role::firstOrCreate(['slug' => 'seller'], ['name' => 'Seller']);
        $seller = User::factory()->create();
        $seller->roles()->attach($role);
        SellerProfile::create([
            'user_id' => $seller->id,
            'display_name' => 'Rich Studio',
            'username' => 'rich-studio-'.$seller->id,
            'country' => 'AE',
            'biography' => 'Approved seller',
            'status' => SellerStatus::Approved,
        ]);

        return $seller;
    }

    private function payload(array $overrides = []): array
    {
        $category = Category::firstOrCreate(['slug' => 'rich-apps'], ['name' => 'Rich Apps']);

        return array_merge([
            'category_id' => $category->id,
            'title' => 'Rich Editor Kit',
            'short_description' => 'A kit with a formatted listing.',
            'description' => str_repeat('Complete listing copy for the product. ', 3),
            'regular_price' => '25.00',
            'version_number' => '1.0.0',
            'release_title' => 'Initial release',
            'archive' => UploadedFile::fake()->create('kit.zip', 10, 'application/zip'),
        ], $overrides);
    }

    public function test_script_tags_are_stripped_from_saved_and_displayed_description(): void
    {
        Storage::fake('local');
        config()->set('app.url', 'http://localhost');
        $copy = str_repeat('Safe product overview text. ', 4);
        $this->actingAs($this->seller())->post('/seller/products', $this->payload([
            'description' => $copy.'<script>alert(1)</script><img src="/storage/editor/listing.png" alt="Preview" onerror="alert(1)" class="rounded-xl"><img src="javascript:alert(1)"><p onclick="alert(1)">More details for buyers.</p>',
        ]))->assertRedirect('/seller/products');

        $product = Product::firstOrFail();
        $this->assertStringContainsString('Safe product overview text.', $product->description);
        $this->assertStringContainsString('More details for buyers.', $product->description);
        $this->assertStringContainsString('<img src="/storage/editor/listing.png" alt="Preview">', $product->description);
        $this->assertStringNotContainsString('<script', $product->description);
        $this->assertStringNotContainsString('onerror', $product->description);
        $this->assertStringNotContainsString('onclick', $product->description);
        $this->assertStringNotContainsString('javascript:', $product->description);
        $this->assertStringNotContainsString('class="rounded-xl"', $product->description);

        $product->update(['status' => ProductStatus::Published, 'published_at' => now()]);
        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertSee('Safe product overview text.')
            ->assertSee('More details for buyers.')
            ->assertSee('rich-content', false)
            ->assertSee('<img src="/storage/editor/listing.png" alt="Preview">', false)
            ->assertDontSee('<script>alert(1)', false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_html_tags_do_not_satisfy_the_description_minimum(): void
    {
        Storage::fake('local');
        $this->actingAs($this->seller())->post('/seller/products', $this->payload([
            'description' => str_repeat('<p><strong></strong></p>', 20),
        ]))->assertSessionHasErrors('description');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_legacy_plain_text_description_still_renders(): void
    {
        $seller = $this->seller();
        $category = Category::firstOrCreate(['slug' => 'rich-apps'], ['name' => 'Rich Apps']);
        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Plain Kit',
            'slug' => 'plain-kit',
            'short_description' => 'Plain blurb.',
            'description' => str_repeat('Detailed CRM functionality. ', 4),
            'regular_price' => '39.00',
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        $this->get('/products/'.$product->slug)->assertOk()->assertSee('Detailed CRM functionality.');
    }
}
