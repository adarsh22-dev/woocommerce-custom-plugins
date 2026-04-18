<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/migrations/class-migrate-legacy.php';
require_once __DIR__ . '/migrations/class-migrate-legacy2.php';

class WP_Reels_Upgrader
{
    const OPT_VERSION  = 'wp_reels_version';
    const OPT_MIG_STAT = 'wp_reels_migration_status'; // pending|running|done|error
    const OPT_MIG_OFFS = 'wp_reels_migration_offset'; // batch offset

    public static function maybe_upgrade()
    {
        $installed = get_option(self::OPT_VERSION);
        if ($installed === WP_REELS_VER) {
            return; // up-to-date
        }

        global $wpdb;
        $p = $wpdb->prefix;

        // Stricter check: if the final migration table 'reels_groups_stories' exists,
        // we can assume the database is modern.
        $groups_stories_table = $p . 'reels_groups_stories';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $groups_stories_table))) {
            update_option(self::OPT_VERSION, WP_REELS_VER, true);
            return;
        }

        // --- Legacy2 check first ---
        $legacy2_status = get_option(WP_Reels_Migrate_Legacy2::OPT_MIG2_STAT);
        $is_legacy2_migration_running = $legacy2_status && in_array($legacy2_status, ['pending', 'error_runtime']);

        $stories_table_name = $wpdb->prefix . 'reels_stories';
        $stories_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $stories_table_name));

        $group_id_column_exists = false;
        if ($stories_table_exists) {
            $group_id_column_exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$stories_table_name} LIKE %s", 'group_id'));
        }

        $legacy2_detected = $is_legacy2_migration_running || $group_id_column_exists;

        if ($legacy2_detected) {
            if ($group_id_column_exists) {
                if (false === $legacy2_status) {
                    update_option(WP_Reels_Migrate_Legacy2::OPT_MIG2_STAT, 'pending', false);
                }
                WP_Reels_Migrate_Legacy2::prepare_data();
            }

            // 1) Ensure schema is current
            WP_Reels_DB::create_or_update_tables();

            // 2) Run migration
            try {
                WP_Reels_Migrate_Legacy2::run_migration(); // Use the prepared data
                WP_Reels_Migrate_Legacy2::drop_legacy_column();
            } catch (\Throwable $e) {
                update_option(WP_Reels_Migrate_Legacy2::OPT_MIG2_STAT, 'error_runtime', false); // different error status
                error_log('[WP Reels] Legacy2 Migration runtime error: ' . $e->getMessage());
            }

            // 3) Store new version so we don't run every request
            update_option(self::OPT_VERSION, WP_REELS_VER, true);
            return;
        }

        // --- If not legacy2, check for legacy ---
        $legacy = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->esc_like($wpdb->prefix . 'ecommreels_tbl')
        ));

        // 1) Ensure schema is current (for legacy1 or new installs)
        WP_Reels_DB::create_or_update_tables();

        // 2) Kick migration ONLY IF pending status
        if ($legacy) {
            $legacy_status = get_option(self::OPT_MIG_STAT, 'done');
            if ($legacy_status !== 'done') {
                update_option(self::OPT_MIG_STAT, 'pending', false); // Ensure pending if it was e.g. 'error'
                update_option(self::OPT_MIG_OFFS, 0, false); // Reset offset or continue from error

                try {
                    WP_Reels_Migrate_Legacy::run_batch();
                } catch (\Throwable $e) {
                    update_option(self::OPT_MIG_STAT, 'error', false);
                    error_log('[WP Reels] Legacy Migration error: ' . $e->getMessage());
                }
            }
        }

        // 3) Store new version so we don't run every request
        update_option(self::OPT_VERSION, WP_REELS_VER, true);
    }
}
