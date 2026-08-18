<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
class Page extends Model
{
 public const HOMEPAGE_SLUG='home';
 protected $fillable=['title','slug','excerpt','body','meta_title','meta_description','settings','status','published_at','created_by'];
 protected function casts(): array {return ['published_at'=>'datetime','settings'=>'array'];}
 public function scopePublished(Builder $query): Builder {return $query->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now());}
 public function isHomepage(): bool {return $this->slug===self::HOMEPAGE_SLUG;}
 public static function homepageDefaults(): array
 {
  return [
   'title'=>'Find the perfect digital assets for your next project.',
   'excerpt'=>'Access a curated library of high-quality scripts, themes, and design tools from top-tier creators worldwide.',
   'body'=>'',
   'meta_title'=>null,
   'meta_description'=>null,
   'status'=>'published',
   'settings'=>self::homepageSettingsDefaults(),
  ];
 }
 public static function homepageSettingsDefaults(): array
 {
  return [
   'hero_badge_text'=>'Reviewed digital products',
   'cta_primary_text'=>'Explore Assets',
   'cta_primary_url'=>'/products',
   'cta_secondary_text'=>'Become a Seller',
   'cta_secondary_url'=>'/register',
   'show_categories'=>true,
   'categories_title'=>'Browse Categories',
   'categories_subtitle'=>'Find the right tools by type.',
   'show_trending'=>true,
   'trending_title'=>'Trending Products',
   'trending_subtitle'=>'The most popular assets this week.',
   'show_new_arrivals'=>true,
   'new_arrivals_title'=>'New Arrivals',
   'new_arrivals_subtitle'=>'Fresh releases from our creator community.',
   'show_featured_creators'=>true,
   'featured_creators_title'=>'Featured creators',
   'featured_creators_subtitle'=>'Independent studios shipping reviewed products.',
  ];
 }
 public function homepageSettings(): array {return array_replace(self::homepageSettingsDefaults(),$this->settings ?? []);}
}
