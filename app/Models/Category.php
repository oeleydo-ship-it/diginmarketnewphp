<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'icon', 'image_path', 'is_active', 'display_order', 'commission_rate'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'commission_rate' => 'decimal:2'];
    }

    /**
     * Keyword → Material Symbols icon, first match wins. Specific technologies sit above
     * generic terms so "JavaScript Applications" resolves to the JS icon, not the apps one.
     */
    private const ICON_KEYWORDS = [
        'php' => 'php', 'wordpress' => 'view_quilt', 'javascript' => 'javascript', 'js' => 'javascript',
        'typescript' => 'javascript', 'laravel' => 'terminal', 'python' => 'data_object', 'html' => 'html',
        'css' => 'css', 'react' => 'code_blocks', 'vue' => 'code_blocks', 'flutter' => 'flutter_dash',
        'android' => 'android', 'ios' => 'phone_iphone', 'iphone' => 'phone_iphone', 'mobile' => 'smartphone',
        'ai' => 'smart_toy', 'ml' => 'smart_toy', 'chatbot' => 'smart_toy', 'blockchain' => 'token',
        'crypto' => 'token', 'game' => 'sports_esports', 'games' => 'sports_esports', 'gaming' => 'sports_esports',
        'audio' => 'music_note', 'music' => 'music_note', 'sound' => 'music_note', 'video' => 'movie',
        'movie' => 'movie', 'photo' => 'photo_camera', 'photos' => 'photo_camera', 'photography' => 'photo_camera',
        'image' => 'photo_camera', 'images' => 'photo_camera', 'font' => 'text_fields', 'fonts' => 'text_fields',
        'typography' => 'text_fields', 'plugin' => 'extension', 'plugins' => 'extension', 'extension' => 'extension',
        'extensions' => 'extension', 'addon' => 'extension', 'addons' => 'extension', 'database' => 'database',
        'sql' => 'database', 'security' => 'shield', 'marketing' => 'trending_up', 'seo' => 'trending_up',
        'email' => 'mail', 'mail' => 'mail', 'newsletter' => 'mail', 'chat' => 'forum', 'social' => 'forum',
        'community' => 'forum', 'education' => 'school', 'course' => 'school', 'courses' => 'school',
        'learning' => 'school', 'finance' => 'payments', 'invoice' => 'payments', 'accounting' => 'payments',
        'payment' => 'payments', 'payments' => 'payments', 'crm' => 'business_center', 'erp' => 'business_center',
        'business' => 'business_center', 'api' => 'api', 'backend' => 'api', 'ecommerce' => 'storefront',
        'shop' => 'storefront', 'store' => 'storefront', 'booking' => 'event', 'calendar' => 'event',
        'health' => 'monitor_heart', 'fitness' => 'monitor_heart', 'map' => 'map', 'travel' => 'map',
        'blog' => 'article', 'cms' => 'article', 'news' => 'article', 'analytics' => 'insights',
        'dashboard' => 'insights', 'ui' => 'palette', 'ux' => 'palette', 'design' => 'palette',
        'graphic' => 'palette', 'graphics' => 'palette', 'template' => 'dashboard_customize',
        'templates' => 'dashboard_customize', 'theme' => 'dashboard_customize', 'themes' => 'dashboard_customize',
        'icon' => 'interests', 'icons' => 'interests', 'script' => 'code', 'scripts' => 'code', 'code' => 'code',
        'tool' => 'build', 'tools' => 'build', 'web' => 'language', 'website' => 'language',
        'app' => 'apps', 'apps' => 'apps', 'application' => 'apps', 'applications' => 'apps',
    ];

    /** The icon to render: the admin's explicit choice, else auto-matched from the name. */
    public function displayIcon(): string
    {
        if ($this->icon) {
            return $this->icon;
        }
        $words = preg_split('/[^a-z0-9]+/', strtolower($this->name.' '.$this->slug)) ?: [];
        foreach (self::ICON_KEYWORDS as $keyword => $icon) {
            if (in_array((string) $keyword, $words, true)) {
                return $icon;
            }
        }

        return 'category';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
