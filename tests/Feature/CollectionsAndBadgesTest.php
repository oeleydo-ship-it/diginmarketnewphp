<?php
namespace Tests\Feature;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CollectionsAndBadgesTest extends TestCase
{
 use RefreshDatabase;

 private function product(string $title = 'Kit', ?User $seller = null): Product
 {
  $seller ??= User::factory()->create();
  SellerProfile::firstOrCreate(['user_id' => $seller->id], ['display_name' => 'Studio '.$seller->id, 'username' => 'studio-'.$seller->id, 'country' => 'AE', 'biography' => 'x', 'status' => 'approved']);
  $category = Category::firstOrCreate(['slug' => 'apps'], ['name' => 'Apps']);
  return Product::create(['seller_id' => $seller->id, 'category_id' => $category->id, 'title' => $title, 'slug' => str($title)->slug().'-'.uniqid(), 'short_description' => 'x', 'description' => 'xxxxx', 'regular_price' => 10, 'status' => ProductStatus::Published, 'published_at' => now()]);
 }

 public function test_buyer_creates_collection_inline_while_saving_a_product(): void
 {
  // Two products from different sellers so the card grid arms the lazy-load guard.
  $first = $this->product('First Kit');
  $second = $this->product('Second Kit');
  $buyer = User::factory()->create();
  $this->actingAs($buyer)->post(route('collections.add', $first), ['new_title' => 'Starter Stack'])->assertRedirect();
  $collection = ProductCollection::firstOrFail();
  $this->assertSame($buyer->id, $collection->user_id);
  $this->post(route('collections.add', $second), ['collection_id' => $collection->id])->assertRedirect();
  $this->assertSame(2, $collection->products()->count());
  $this->get('/collections')->assertOk()->assertSee('Starter Stack');
 }

 public function test_public_collection_page_renders_for_guests_and_private_stays_hidden(): void
 {
  $buyer = User::factory()->create();
  $public = ProductCollection::create(['user_id' => $buyer->id, 'title' => 'Open Picks', 'slug' => 'open-picks', 'is_public' => true]);
  $public->products()->attach([$this->product('Alpha')->id, $this->product('Beta')->id]);
  $private = ProductCollection::create(['user_id' => $buyer->id, 'title' => 'Secret Stash', 'slug' => 'secret-stash', 'is_public' => false]);
  $this->get('/collections/open-picks')->assertOk()->assertSee('Open Picks')->assertSee('Alpha');
  $this->get('/collections/secret-stash')->assertNotFound();
  // The owner can still open their private collection.
  $this->actingAs($buyer)->get('/collections/secret-stash')->assertOk()->assertSee('Secret Stash');
 }

 public function test_only_the_owner_can_modify_a_collection(): void
 {
  $owner = User::factory()->create();
  $collection = ProductCollection::create(['user_id' => $owner->id, 'title' => 'Mine', 'slug' => 'mine-1', 'is_public' => true]);
  $product = $this->product();
  $collection->products()->attach($product->id);
  $stranger = User::factory()->create();
  $this->actingAs($stranger)->delete(route('collections.remove', [$collection, $product]))->assertForbidden();
  $this->delete(route('collections.destroy', $collection))->assertForbidden();
  $this->assertDatabaseHas('collections', ['id' => $collection->id]);
 }

 public function test_storefront_shows_computed_achievement_badges(): void
 {
  $seller = User::factory()->create(['created_at' => now()->subYears(2)]);
  $this->product('Seed', $seller);
  $seller->products()->first()->update(['sales_count' => 60]);
  $username = $seller->sellerProfile->username;
  $this->get('/authors/'.$username)->assertOk()->assertSee('Top Seller')->assertSee('Veteran')->assertDontSee('Power Author');
 }
}
