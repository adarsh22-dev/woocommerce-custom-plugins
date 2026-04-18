<?php

namespace ReelsWP\domain\services;

class RateLimiter
{

    /**
     * Simple transient-based limiter.
     * Returns true if allowed, false if exceeded.
     */
    public static function check(string $key, int $limit, int $seconds): bool
    {
        $transKey = 'reelswp_rl_' . md5($key);
        $record   = get_transient($transKey);

        if (!$record) {
            $record = ['count' => 1, 'expires' => time() + $seconds];
            set_transient($transKey, $record, $seconds);
            return true;
        }

        if ($record['count'] >= $limit) {
            return false;
        }

        $record['count']++;
        set_transient($transKey, $record, $record['expires'] - time());
        return true;
    }
}
