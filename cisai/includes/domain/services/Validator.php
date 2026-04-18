<?php

namespace ReelsWP\domain\services;

class Validator
{

    /** Quick sanitize + validate for colors */
    public static function sanitize_color(?string $hex): ?string
    {
        if (!$hex) return null;
        $hex = trim($hex);
        return preg_match('/^#?[0-9a-fA-F]{3,6}$/', $hex) ? ltrim($hex, '#') : null;
    }

    /** Ensure URL safe */
    public static function sanitize_url(?string $url): ?string
    {
        return $url ? esc_url_raw($url) : null;
    }

    /** Enforce string length */
    public static function limit_str(?string $s, int $max = 255): ?string
    {
        return $s ? mb_substr(sanitize_text_field($s), 0, $max) : null;
    }

    /** Ensure float within range */
    public static function bound_float($f, float $min, float $max): ?float
    {
        if ($f === null) return null;
        $f = (float)$f;
        return ($f < $min || $f > $max) ? null : $f;
    }
}
