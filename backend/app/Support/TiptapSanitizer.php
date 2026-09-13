<?php

namespace App\Support;

use RuntimeException;

/**
 * Strip everything not on the whitelist from a Tiptap document
 * (2026-09-13-005 Phase 007, D-018).
 *
 * Runs on EVERY content write — page create/update here, and Phase 009's sync
 * push. Client JSON is never trusted: a crafted document could otherwise carry
 * node types the editor would happily render.
 *
 * The whitelist lives in config/notebook.php, the PHP mirror of ALLOWED_NODES
 * in @notebook/types. Change both together.
 */
class TiptapSanitizer
{
    /**
     * @param  array<string, mixed>|null  $content
     * @return array<string, mixed>|null
     *
     * @throws RuntimeException when the document exceeds the size cap
     */
    public static function clean(?array $content): ?array
    {
        if ($content === null) {
            return null;
        }

        $encoded = json_encode($content);
        $max = (int) config('notebook.max_page_content_bytes', 512 * 1024);

        if ($encoded === false || strlen($encoded) > $max) {
            throw new RuntimeException("Page content exceeds the {$max} byte limit.");
        }

        // A page is always a doc at the root, whatever the client claimed.
        $children = isset($content['content']) && is_array($content['content'])
            ? self::cleanNodes($content['content'], 1)
            : [];

        return ['type' => 'doc', 'content' => $children];
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return list<array<string, mixed>>
     */
    private static function cleanNodes(array $nodes, int $depth): array
    {
        if ($depth > (int) config('notebook.max_content_depth', 50)) {
            return [];
        }

        $allowed = config('notebook.allowed_nodes', []);
        $out = [];

        foreach ($nodes as $node) {
            if (! is_array($node) || ! isset($node['type']) || ! is_string($node['type'])) {
                continue;
            }

            // 'doc' is only ever the root — a nested one would be malformed.
            if ($node['type'] === 'doc' || ! in_array($node['type'], $allowed, true)) {
                continue;
            }

            $clean = ['type' => $node['type']];

            if (isset($node['text']) && is_string($node['text'])) {
                $clean['text'] = $node['text'];
            }

            if (isset($node['attrs']) && is_array($node['attrs'])) {
                $attrs = self::cleanAttrs($node['type'], $node['attrs']);
                if ($attrs !== []) {
                    $clean['attrs'] = $attrs;
                }
            }

            if (isset($node['marks']) && is_array($node['marks'])) {
                $marks = self::cleanMarks($node['marks']);
                if ($marks !== []) {
                    $clean['marks'] = $marks;
                }
            }

            if (isset($node['content']) && is_array($node['content'])) {
                $clean['content'] = self::cleanNodes($node['content'], $depth + 1);
            }

            $out[] = $clean;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private static function cleanAttrs(string $type, array $attrs): array
    {
        // Heading levels are capped — a level 6 would render outside the paper's
        // type scale and break the line-height sync that IS the product (D-010).
        if ($type === 'heading') {
            $levels = config('notebook.allowed_heading_levels', [1, 2]);
            $level = (int) ($attrs['level'] ?? 1);

            return ['level' => in_array($level, $levels, true) ? $level : $levels[0]];
        }

        // Scalars only: an object or array attr is a vector for smuggling
        // structure past the node walk.
        return array_filter(
            $attrs,
            fn ($value) => $value === null || is_scalar($value),
        );
    }

    /**
     * @param  array<int, mixed>  $marks
     * @return list<array<string, mixed>>
     */
    private static function cleanMarks(array $marks): array
    {
        $allowed = config('notebook.allowed_marks', []);
        $out = [];

        foreach ($marks as $mark) {
            if (! is_array($mark) || ! isset($mark['type']) || ! is_string($mark['type'])) {
                continue;
            }

            if (in_array($mark['type'], $allowed, true)) {
                $out[] = ['type' => $mark['type']];
            }
        }

        return $out;
    }
}
