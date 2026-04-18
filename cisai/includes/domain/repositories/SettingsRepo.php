<?php
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

namespace ReelsWP\domain\repositories;

defined('ABSPATH') || exit;

class SettingsRepo extends BaseRepo
{

    /**
     * Get settings (assuming single row).
     */
    public function get(): ?array
    {
        $row = $this->wpdb->get_row(
            "SELECT id, rate_limit, time_limit 
               FROM {$this->p}reels_settings 
              ORDER BY id ASC 
              LIMIT 1",
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Create settings row (useful on first activation).
     */
    public function create(array $in): int
    {
        $rate = array_key_exists('rate_limit', $in) ? (int)$in['rate_limit'] : null;
        $time = array_key_exists('time_limit', $in) ? (int)$in['time_limit'] : null;

        $this->wpdb->insert("{$this->p}reels_settings", [
            'rate_limit' => $rate,
            'time_limit' => $time,
        ], ['%d', '%d']);

        return (int)$this->wpdb->insert_id;
    }

    /**
     * Update settings (assumes single row, or pass ID).
     */
    public function update(int $id, array $in): int
    {
        $fields = [];
        $fmts = [];
        $map = ['rate_limit' => '%d', 'time_limit' => '%d'];
        foreach ($map as $k => $fmt) {
            if (array_key_exists($k, $in)) {
                $fields[$k] = $in[$k];
                $fmts[] = $fmt;
            }
        }
        if ($fields) {
            $this->wpdb->update("{$this->p}reels_settings", $fields, ['id' => $id], $fmts, ['%d']);
        }
        return $id;
    }

    /**
     * Upsert settings (if no row, create one).
     */
    public function save(array $in): int
    {
        $row = $this->get();
        if ($row) {
            return $this->update((int)$row['id'], $in);
        }
        return $this->create($in);
    }

    /**
     * Static wrapper for controller calls.
     */
    public static function get_settings(): ?array
    {
        $instance = new self();
        return $instance->get();
    }
}
