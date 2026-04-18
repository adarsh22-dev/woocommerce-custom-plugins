<?php

defined('ABSPATH') || exit;

class WP_Reels_Migrate_Legacy2
{
    const OPT_MIG2_STAT_DATA = 'wp_reels_migration_l2_data'; // stores story_id/group_id pairs
    const OPT_MIG2_STAT = 'wp_reels_migration_status_l2';

    public static function prepare_data()
    {
        global $wpdb;
        $p = $wpdb->prefix;

        // Fetch all story_id, group_id from existing reels_stories (legacy2 format)
        // This is safe because prepare_data is called BEFORE dbDelta potentially alters the table.
        $story_group_pairs = $wpdb->get_results(
            "SELECT id as story_id, group_id FROM {$p}reels_stories WHERE group_id IS NOT NULL", ARRAY_A
        );

        if (!empty($story_group_pairs)) {
            update_option(self::OPT_MIG2_STAT_DATA, $story_group_pairs, false);
            update_option(self::OPT_MIG2_STAT, 'pending', false);
        } else {
            // No data to migrate, mark as done
            update_option(self::OPT_MIG2_STAT, 'done', false);
        }
    }

    public static function run_migration()
    {

        global $wpdb;
        $p = $wpdb->prefix;

        $story_group_pairs = get_option(self::OPT_MIG2_STAT_DATA, []);

        if (empty($story_group_pairs)) {
            update_option(self::OPT_MIG2_STAT, 'done', false);
            return;
        }

        // Insert into the new pivot table
        foreach ($story_group_pairs as $pair) {
            $story_id = (int)$pair['story_id'];
            $group_id = (int)$pair['group_id'];

            if ($story_id && $group_id) {
                $wpdb->replace("{$p}reels_groups_stories", [
                    'group_id' => $group_id,
                    'story_id' => $story_id,
                ], ['%d', '%d']);
            }
        }

        // Migration complete
        update_option(self::OPT_MIG2_STAT, 'done', false);
        delete_option(self::OPT_MIG2_STAT_DATA); // Clean up temporary data
    }

    public static function drop_legacy_column()
    {
        global $wpdb;

        $stories_table = $wpdb->prefix . 'reels_stories';
        $wpdb->query("ALTER TABLE {$stories_table} DROP COLUMN group_id, DROP COLUMN order_index, DROP COLUMN is_active");

        $groups_table = $wpdb->prefix . 'reels_groups';
        $wpdb->query("ALTER TABLE {$groups_table} DROP COLUMN render_json, DROP COLUMN order_index");

        $files_table = $wpdb->prefix . 'reels_files';
        $wpdb->query("ALTER TABLE {$files_table} DROP COLUMN order_index");
    }
}
