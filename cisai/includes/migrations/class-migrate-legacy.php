<?php

defined('ABSPATH') || exit;

class WP_Reels_Migrate_Legacy
{
    const BATCH = 50;

    public static function run_batch()
    {
        $status = get_option(WP_Reels_Upgrader::OPT_MIG_STAT, 'done');
        if ($status === 'done') return;

        global $wpdb;
        $p = $wpdb->prefix;
        $offset = (int) get_option(WP_Reels_Upgrader::OPT_MIG_OFFS, 0);

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT id, group_name, group_stories FROM {$p}ecommreels_tbl ORDER BY id ASC LIMIT %d OFFSET %d", self::BATCH, $offset),
            ARRAY_A
        );
        if (!$rows) {
            update_option(WP_Reels_Upgrader::OPT_MIG_STAT, 'done', false);
            return;
        }

        update_option(WP_Reels_Upgrader::OPT_MIG_STAT, 'running', false);

        foreach ($rows as $row) {
            self::migrate_row($row);
        }

        // advance offset
        update_option(WP_Reels_Upgrader::OPT_MIG_OFFS, $offset + count($rows), false);

        // If you prefer background, you can schedule next batch via wp_cron here.
    }

    private static function migrate_row(array $row)
    {
        global $wpdb;
        $p = $wpdb->prefix;

        $id = $row['id'];
        $gname = $row['group_name'] ?: 'untitled';
        $slug  = sanitize_title($gname);
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$p}reels_groups WHERE slug = %s",
            $slug
        ));
        if ($existing) {
            $slug .= '-' . $id; // append ID to make it unique
        }
        // Insert or get group (slug unique)
        $wpdb->replace("{$p}reels_groups", [
            'id'        => $id,
            'slug'        => $slug,
            'group_name'  => $gname,
            'styles_json' => null,
            'created_by'  => null,
        ], ['%d', '%s', '%s', '%s', '%d']);

        $group_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$p}reels_groups WHERE slug=%s",
            $slug
        ));

        $json = json_decode($row['group_stories'], true);
        if (!$json) return;

        if (!empty($json['styles'])) {
            $wpdb->update(
                "{$p}reels_groups",
                ['styles_json' => wp_json_encode($json['styles'])],
                ['id' => $group_id],
                ['%s'],
                ['%d']
            );
        }

        foreach (($json['stories'] ?? []) as $s) {
            $story_uuid = !empty($s['_id']) ? $s['_id'] : wp_generate_uuid4();

            $wpdb->replace("{$p}reels_stories", [
                'story_uuid'  => $story_uuid,
                'title'       => $s['title'] ?? null,
                'view_count'  => (int) ($s['views'] ?? 0),
            ], ['%s', '%s', '%d']);

            $story_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$p}reels_stories WHERE story_uuid=%s",
                $story_uuid
            ));

            if ($story_id && $group_id) {
                $wpdb->replace("{$p}reels_groups_stories", [
                    'group_id' => $group_id,
                    'story_id' => $story_id,
                ], ['%d', '%d']);
            }

            foreach (($s['files'] ?? []) as $f) {
                $file_uuid = !empty($f['_id']) ? $f['_id'] : wp_generate_uuid4();

                // Collect property arrays
                $textProps    = $f['textProperties']    ?? []; // if you had them
                $buttonProps  = $f['buttonProperties']  ?? [];
                $imageProps   = $f['imageProperties']   ?? [];

                // Encode to JSON (null if empty to save space)
                $textJson     = !empty($textProps) ? wp_json_encode($textProps) : null;
                $buttonJson   = !empty($buttonProps) ? wp_json_encode($buttonProps) : null;
                $imageJson    = !empty($imageProps) ? wp_json_encode($imageProps) : null;

                $wpdb->replace("{$p}reels_files", [
                    'story_id'               => $story_id,
                    'file_uuid'              => $file_uuid,
                    'wp_media_id'            => isset($f['id']) ? (int) $f['id'] : null,
                    'url'                    => $f['url'] ?? '',
                    'mime_type'              => $f['type'] ?? 'application/octet-stream',
                    'text_properties'     => $textJson,
                    'button_properties' => $buttonJson,
                    'image_properties'  => $imageJson,
                ], [
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]);

                if (!empty($buttonProps)) {
                    foreach ($buttonProps as $button) {
                        if (empty($button['_id'])) {
                            continue;
                        }
                        $button_uuid = $button['_id'];
                        $table = "{$p}reels_button_clicks";

                        $wpdb->insert(
                            $table,
                            [
                                'group_id'      => $group_id,
                                'story_id'      => $story_id,
                                'story_title'   => $s['title'] ?? '',
                                'btn_uuid'      => $button_uuid,
                                'button_text'   => $button['buttonText'] ?? null,
                                'button_url'    => $button['buttonUrl'] ?? null,
                                'campaign_name' => $button['campaignName'] ?? null,
                                'click_count'   => (int) ($button['clickCount'] ?? 0),
                            ],
                            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d']
                        );
                    }
                }
            }
        }
    }
}
