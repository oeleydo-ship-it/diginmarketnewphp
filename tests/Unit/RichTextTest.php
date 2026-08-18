<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    public function test_script_tags_and_event_handlers_are_stripped(): void
    {
        $html = '<p>Safe intro.</p><script>alert(1)</script><p onclick="alert(1)">Click</p><a href="javascript:alert(1)">Bad</a><a href="https://example.com">Good</a>';
        $clean = RichText::sanitize($html);

        $this->assertStringContainsString('Safe intro.', $clean);
        $this->assertStringContainsString('Click', $clean);
        $this->assertStringContainsString('https://example.com', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', RichText::sanitize('<p>Hi</p><iframe src="https://evil.test"></iframe>'));
    }

    public function test_images_keep_only_safe_attributes_and_sources(): void
    {
        putenv('APP_URL=https://diginmarket.test');

        $html = '<p>Body</p><img src="https://diginmarket.test/storage/editor/example.png" alt="Screenshot" onerror="alert(1)" class="bad"><img src="javascript:alert(1)" alt="Bad"><img src="https://cdn.example.com/outside.png" alt="Outside">';
        $clean = RichText::sanitize($html);

        $this->assertStringContainsString('src="https://diginmarket.test/storage/editor/example.png"', $clean);
        $this->assertStringContainsString('alt="Screenshot"', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('class="bad"', $clean);
        $this->assertStringNotContainsString('javascript:alert(1)', $clean);
        $this->assertStringNotContainsString('https://cdn.example.com/outside.png', $clean);
    }

    public function test_plain_text_display_preserves_line_breaks(): void
    {
        $html = RichText::toHtml("Line one\nLine two");

        $this->assertStringContainsString("Line one<br>\nLine two", $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_plain_length_ignores_tags(): void
    {
        $this->assertSame(4, RichText::plainLength('<p><strong>Abcd</strong></p>'));
        $this->assertSame(0, RichText::plainLength(str_repeat('<b></b>', 20)));
    }
}
