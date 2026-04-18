<?php

namespace ReelsWP\domain\services;

class Analytics
{

    public static function log_event(string $type, array $ctx = []): void
    {
        global $wpdb;
        $p = $wpdb->prefix;

        $wpdb->insert("{$p}reels_events", [
            'story_id'   => $ctx['story_id'] ?? null,
            'button_id'  => $ctx['button_id'] ?? null,
            'event_type' => $type,
            'user_ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'occurred_at' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s']);
    }
}
