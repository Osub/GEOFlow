<?php

namespace App\Support\Site;

use App\Support\GeoFlow\ImageUrlNormalizer;
use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Keeps article-authored HTML useful while stripping executable markup.
 */
final class HtmlSanitizer
{
    private const ROOT_ID = 'geoflow-html-sanitizer-root';

    private const ALLOWED_TAGS = [
        'a', 'article', 'b', 'blockquote', 'br', 'code', 'del', 'div', 'em', 'figcaption',
        'figure', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins',
        'kbd', 'li', 'mark', 'ol', 'p', 'pre', 's', 'samp', 'small', 'span',
        'section', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'th', 'thead', 'tr',
        'u', 'ul',
    ];

    private const REMOVE_WITH_CONTENT = [
        'base', 'button', 'embed', 'form', 'iframe', 'input', 'link', 'math',
        'meta', 'object', 'option', 'script', 'select', 'style', 'svg',
        'textarea',
    ];

    private const TAG_ATTRIBUTES = [
        'a' => ['href', 'rel', 'target', 'title'],
        'code' => ['class'],
        'img' => ['alt', 'height', 'src', 'title', 'width'],
        'ol' => ['start', 'type'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    private const GLOBAL_ATTRIBUTES = ['aria-label', 'title'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="'.self::ROOT_ID.'">'.$html.'</div>',
            LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        self::removeProcessingInstructions($document);

        $root = $document->getElementById(self::ROOT_ID);
        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeChildren($root);

        return self::innerHtml($root);
    }

    private static function removeProcessingInstructions(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//processing-instruction()') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            self::sanitizeElement($child);
        }
    }

    private static function sanitizeElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
            $element->parentNode?->removeChild($element);

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            self::sanitizeChildren($element);
            self::unwrap($element);

            return;
        }

        self::sanitizeAttributes($element, $tag);
        self::sanitizeChildren($element);

        if ($tag === 'img' && ! $element->hasAttribute('src')) {
            $element->parentNode?->removeChild($element);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! $attribute instanceof DOMAttr) {
                continue;
            }

            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            if (str_starts_with($name, 'on') || str_contains($name, ':')) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            $allowed = array_merge(self::GLOBAL_ATTRIBUTES, self::TAG_ATTRIBUTES[$tag] ?? []);
            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'href' && ! self::isSafeUrl($value, false)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'src') {
                if (! self::isSafeUrl($value, true)) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }

                $normalized = ImageUrlNormalizer::toPublicUrl($value);
                if (! self::isSafeUrl($normalized, true)) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }

                $element->setAttribute($name, $normalized);
                continue;
            }

            if (in_array($name, ['height', 'width', 'colspan', 'rowspan', 'start'], true) && ! preg_match('/^\d{1,4}$/', $value)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'class' && ! self::sanitizeCodeClass($element, $value)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'target' && ! in_array($value, ['_blank', '_self', '_parent', '_top'], true)) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function sanitizeCodeClass(DOMElement $element, string $value): bool
    {
        if (strtolower($element->tagName) !== 'code') {
            return false;
        }

        $classes = preg_split('/\s+/', $value) ?: [];
        $classes = array_values(array_filter(
            $classes,
            static fn (string $class): bool => preg_match('/^language-[a-z0-9_-]+$/i', $class) === 1
        ));

        if ($classes === []) {
            return false;
        }

        $element->setAttribute('class', implode(' ', $classes));

        return true;
    }

    private static function isSafeUrl(string $url, bool $image): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $url = preg_replace('/[\x00-\x20\x7F]+/u', '', $url) ?? $url;

        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, './') || str_starts_with($url, '../')) {
            return true;
        }

        $allowedSchemes = $image ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            return in_array(strtolower($scheme), $allowedSchemes, true);
        }

        return ! preg_match('/^[a-z][a-z0-9+.-]*:/i', $url);
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild instanceof DOMNode) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
