<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichText
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        return self::sanitizeImages(self::sanitizer()->sanitize($html));
    }

    /**
     * Safe HTML for display. Legacy plain-text descriptions keep their line breaks.
     */
    public static function toHtml(?string $value): string
    {
        $value = (string) $value;
        if (trim($value) === '') {
            return '';
        }

        if (! preg_match('/<[a-z][a-z0-9]*\b/i', $value)) {
            return nl2br(e($value), false);
        }

        return self::sanitize($value);
    }

    public static function plainLength(?string $value): int
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strlen(trim($text));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        if (self::$sanitizer) {
            return self::$sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->withMaxInputLength(100_000)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('a', ['href'])
            ->allowElement('img', ['src', 'alt'])
            ->allowElement('blockquote')
            ->allowElement('code')
            ->allowElement('pre')
            ->dropElement('script')
            ->dropElement('iframe')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('form')
            ->dropElement('input')
            ->dropElement('textarea')
            ->dropElement('style')
            ->dropElement('link')
            ->dropElement('meta')
            ->dropElement('svg')
            ->dropElement('video')
            ->dropElement('audio');

        return self::$sanitizer = new HtmlSanitizer($config);
    }

    private static function sanitizeImages(string $html): string
    {
        if (! str_contains($html, '<img')) {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $wrapper = $dom->getElementsByTagName('div')->item(0);
        if (! $wrapper instanceof DOMElement) {
            return $html;
        }

        foreach (iterator_to_array($wrapper->getElementsByTagName('img')) as $image) {
            $src = trim($image->getAttribute('src'));

            if (! self::safeImageSource($src)) {
                $image->parentNode?->removeChild($image);
                continue;
            }

            $alt = trim($image->getAttribute('alt'));

            while ($image->attributes->length > 0) {
                $image->removeAttributeNode($image->attributes->item(0));
            }

            $image->setAttribute('src', $src);
            if ($alt !== '') {
                $image->setAttribute('alt', mb_substr($alt, 0, 255));
            }
        }

        $clean = '';
        foreach ($wrapper->childNodes as $child) {
            $clean .= $dom->saveHTML($child);
        }

        return $clean;
    }

    private static function safeImageSource(string $src): bool
    {
        if ($src === '' || preg_match('/^\s*(?:javascript|data|vbscript):/i', $src)) {
            return false;
        }

        if (str_starts_with($src, '/')) {
            return true;
        }

        $scheme = parse_url($src, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = parse_url($src, PHP_URL_HOST);
        $appHost = parse_url(self::appUrl(), PHP_URL_HOST);

        return $host !== null && $appHost !== null && strcasecmp($host, $appHost) === 0;
    }

    private static function appUrl(): string
    {
        if (function_exists('app') && app()->bound('config')) {
            return (string) config('app.url');
        }

        return (string) (getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? ''));
    }
}
