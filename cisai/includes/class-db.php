<?php
defined('ABSPATH') || exit;

class WP_Reels_DB
{
    public static function activate()
    {
        self::create_or_update_tables();
        self::insert_default_settings();
    }

    public static function create_or_update_tables()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;

        // Groups table
        $sql_groups = "CREATE TABLE {$p}reels_groups (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        slug VARCHAR(191) NOT NULL,
        group_name VARCHAR(191) NOT NULL,
        styles_json LONGTEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug),
        PRIMARY KEY (id)
    ) $charset;";

        // Stories table
        $sql_stories = "CREATE TABLE {$p}reels_stories (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        story_uuid CHAR(36) NOT NULL,
        title VARCHAR(255) NULL,
        view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_story_uuid (story_uuid),
        PRIMARY KEY (id)
    ) $charset;";

        // Groups–Stories pivot table (many-to-many)
        $sql_groups_stories = "CREATE TABLE {$p}reels_groups_stories (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        group_id BIGINT UNSIGNED NOT NULL,
        story_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_group_story (group_id, story_id),
        PRIMARY KEY (id),
        KEY idx_group_id (group_id),
        KEY idx_story_id (story_id)
    ) $charset;";

        // Files table (story media files)
        $sql_files = "CREATE TABLE {$p}reels_files (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        story_id BIGINT UNSIGNED NOT NULL,
        file_uuid CHAR(36) NOT NULL,
        wp_media_id BIGINT UNSIGNED NULL,
        url TEXT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        text_properties LONGTEXT NULL,
        button_properties LONGTEXT NULL,
        image_properties LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_file_uuid (file_uuid),
        KEY idx_story_id (story_id),
        PRIMARY KEY (id)
    ) $charset;";

        // Button click stats
        $sql_button_stats = "CREATE TABLE {$p}reels_button_clicks (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        group_id BIGINT UNSIGNED NOT NULL,
        story_id BIGINT UNSIGNED NOT NULL,
        story_title VARCHAR(255) NOT NULL,
        btn_uuid CHAR(36) NOT NULL,
        button_text VARCHAR(255) NULL,
        button_url TEXT NULL,
        campaign_name VARCHAR(191) NULL,
        click_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
        UNIQUE KEY uniq_button (btn_uuid),
        PRIMARY KEY (id)
    ) $charset;";

        // Plugin settings
        $sql_settings = "CREATE TABLE {$p}reels_settings (
        id BIGINT UNSIGNED AUTO_INCREMENT,
        rate_limit INT UNSIGNED NULL,
        time_limit INT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

        // Run all table creations
        dbDelta($sql_groups);
        dbDelta($sql_stories);
        dbDelta($sql_groups_stories);
        dbDelta($sql_files);
        dbDelta($sql_button_stats);
        dbDelta($sql_settings);
    }

    public static function insert_default_settings()
    {
        global $wpdb;
        $p = $wpdb->prefix;

        // Only insert if no row exists
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM {$p}reels_settings");
        if (!$exists) {
            $wpdb->insert("{$p}reels_settings", [
                'rate_limit' => 2,
                'time_limit' => 1,
            ], ['%d', '%d']);
        }
    }
}
