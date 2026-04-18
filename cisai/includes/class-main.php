<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 *	Main class
*/

class Ecommreels_Main {
    public function __construct()
    {
        // $this->ecommreels_load_plugin_textdomain();
        $this->ecommreels_load_file_dependecies();
        $this->ecommreels_install_tables();
    }

    // public function ecommreels_load_plugin_textdomain()
    // {
    //     load_plugin_textdomain( 'ecomm-reels', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    // }

    public function ecommreels_load_file_dependecies()
    {
        $files = [
            'admin/' . ECOMMREELS_FILE_PREFIX . 'reel-admin.php',
            'public/' . ECOMMREELS_FILE_PREFIX . 'reel-public.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'reels-info.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'single-reel-info.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'create-reel.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'delete-reel.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'store-clicks.php',
            'api/' . ECOMMREELS_FILE_PREFIX . 'reel-views.php',
        ];

        foreach ( $files as $file ) {
            $path = ECOMMREELS_PATH . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    public function ecommreels_install_tables()
    {
        global  $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->prefix . 'ecommreels_tbl' ) ) != $wpdb->prefix . 'ecommreels_tbl' ) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE " . $wpdb->prefix . 'ecommreels_tbl' . " (
                id INT(11) NOT NULL AUTO_INCREMENT,
                group_name TEXT,
                group_stories LONGTEXT,
                PRIMARY KEY (id)
            ) {$charset_collate};";
            
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }

        // Set default options
		if ( !get_option( 'ecommreels_rate_limit' ) ) {
        	add_option( 'ecommreels_rate_limit', 2 );
    	}
        
		if ( !get_option( 'ecommreels_time_limit' ) ) {
        	add_option( 'ecommreels_time_limit', 1440 );
    	}
        
    }
}

new Ecommreels_Main();

?>