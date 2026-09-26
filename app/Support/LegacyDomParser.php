<?php

namespace App\Support;

use DOMDocument;
use DOMNode;
use Symfony\Component\HtmlSanitizer\Parser\ParserInterface;

/**
 * HTML parser backed by PHP's classic DOM extension.
 *
 * Symfony's default {@see \Symfony\Component\HtmlSanitizer\Parser\NativeParser}
 * relies on the PHP 8.4 \Dom\HTMLDocument API. On PHP 8.2/8.3 that class does
 * not exist, so requests that sanitize rich text fatal with
 * "Class \"Dom\HTMLDocument\" not found". We parse with the classic
 * DOMDocument instead; the sanitizer's DomVisitor already accepts \DOMNode
 * trees, so the sanitized output is equivalent.
 */
final class LegacyDomParser implements ParserInterface
{
    public function parse(string $html, string $context = 'body'): ?DOMNode
    {
        $document = new DOMDocument;

        // Force UTF-8 handling and mirror NativeParser's context wrapping.
        $source = '<?xml encoding="utf-8" ?>'.sprintf('<!DOCTYPE html><%s>%s</%1$s>', $context, $html);

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($source, LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return null;
        }

        $element = $document->getElementsByTagName($context)->item(0);

        return $element instanceof DOMNode && $element->hasChildNodes() ? $element : null;
    }
}
