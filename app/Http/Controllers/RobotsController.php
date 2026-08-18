<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $sitemapUrl = url(route('sitemap'));
        $includeSitemap = Setting::enabled('seo.sitemap_in_robots', true);
        $robotsExtra = trim((string) Setting::get('seo.robots_extra', ''));

        $lines = [
            'User-agent: *',
            'Disallow:',
            // Some (older) crawlers look for this directive; harmless for modern ones.
            'Host: '.$request->getHost(),
        ];

        if ($includeSitemap) {
            $lines[] = 'Sitemap: '.$sitemapUrl;
        }

        if ($robotsExtra !== '') {
            // Append any extra user-provided directives as-is.
            foreach (preg_split("/\\r\\n|\\r|\\n/", $robotsExtra) as $line) {
                $line = trim($line);
                if ($line !== '') $lines[] = $line;
            }
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}

