<?php

namespace App\Support;

/**
 * Flatten a Tiptap document to plain text (2026-09-13-005 Phase 007).
 *
 * ============================ TWO-SIDED CONTRACT ============================
 * This mirrors flattenPageContent() in packages/utility/page-search.ts, which
 * client-side offline search uses. Both must produce the SAME string for the
 * same document, or the device and the server disagree about what a page says.
 * ===========================================================================
 */
class TiptapText
{
    /**
     * @param  array<string, mixed>|null  $content
     */
    public static function flatten(?array $content): string
    {
        if ($content === null || ! isset($content['content']) || ! is_array($content['content'])) {
            return '';
        }

        $parts = self::collect($content['content']);

        // Collapse runs of whitespace, exactly as the TS side does.
        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)));
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return list<string>
     */
    private static function collect(array $nodes): array
    {
        $out = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (isset($node['text']) && is_string($node['text'])) {
                $out[] = $node['text'];
            }

            if (isset($node['content']) && is_array($node['content'])) {
                foreach (self::collect($node['content']) as $text) {
                    $out[] = $text;
                }
            }
        }

        return $out;
    }
}
