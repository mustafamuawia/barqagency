<?php

namespace App\Services\Pdf;

use ArPHP\I18N\Arabic;

class ArabicShaper
{
    private Arabic $arabic;

    public function __construct()
    {
        $this->arabic = new Arabic();
    }

    public function shape(?string $text): ?string
    {
        if (empty($text) || !preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return $text;
        }

        return $this->arabic->utf8Glyphs($text, 1000, false);
    }

    /**
     * dompdf has no Arabic shaping/bidi engine of its own, so every Arabic run in the
     * rendered HTML (both static template labels and interpolated data) must be
     * pre-shaped into presentation-form glyphs before dompdf ever sees it. Doing this as a
     * whole-document pass (rather than shaping individual fields before they reach the view)
     * guarantees no Arabic string is ever missed just because a template forgot to wrap it.
     */
    public function shapeHtml(string $html): string
    {
        return preg_replace_callback(
            '/[\x{0600}-\x{06FF}][\x{0600}-\x{06FF}0-9\s.,:;\-\/()!"\']*/u',
            function (array $m) {
                // utf8Glyphs reorders its input into visual order, which moves trailing
                // whitespace to the front of its output. Left in place, that swallows the
                // space that was separating this run from whatever follows it in the DOM
                // (e.g. "نظام Barq Agency" collapsing into "نظامBarq Agency"). Shape only
                // the trimmed core and re-append the untouched trailing whitespace after.
                preg_match('/^(.*?)(\s*)$/su', $m[0], $parts);
                [, $core, $trailingSpace] = $parts;

                if ($core === '') {
                    return $m[0];
                }

                return $this->arabic->utf8Glyphs($core, 1000, false) . $trailingSpace;
            },
            $html
        );
    }
}
