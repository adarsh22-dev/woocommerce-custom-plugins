<?php
/**
 * Plugin Name: CISAI WCFM Vendor Chat (Free) - Popup Chat for Vendors
 * Plugin URI: https://example.com/
 * Description: Real-time-ish vendor-to-vendor chat for WCFM Free. Popup/tab style chat available only to vendors. Supports individual chats, groups, search, create group, exit group, delete chat and block vendor. Mobile responsive. Single-file plugin for easy installation.
 * Version: 1.5.29 // Optimized polling: Auto-refresh only on send/receive new messages; reduced constant list reloads for smoother UI.
 * Author: Adarsh Singh
 * Text Domain: wcfm-vendor-chat
 * License: GPL2
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $wcfm_vchat_db_version;
$wcfm_vchat_db_version = '1.5.29'; // Updated for polling optimizations
register_activation_hook( __FILE__, 'wcfm_vchat_activate' );
function wcfm_vchat_activate() {
    global $wpdb, $wcfm_vchat_db_version;
    $charset_collate = $wpdb->get_charset_collate();
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    $groups_table = $wpdb->prefix . 'wcfm_vchat_groups';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $blocks_table = $wpdb->prefix . 'wcfm_vchat_blocks';
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $sql = "
    CREATE TABLE $messages_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_id BIGINT(20) UNSIGNED DEFAULT NULL,
        sender_id BIGINT(20) UNSIGNED NOT NULL,
        receiver_id BIGINT(20) UNSIGNED DEFAULT NULL,
        content LONGTEXT NOT NULL,
        is_group TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX (group_id),
        INDEX (sender_id),
        INDEX (receiver_id)
    ) $charset_collate;
    CREATE TABLE $groups_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        created_by BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;
    CREATE TABLE $participants (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY group_user (group_id,user_id)
    ) $charset_collate;
    CREATE TABLE $blocks_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        blocker_id BIGINT(20) UNSIGNED NOT NULL,
        blocked_id BIGINT(20) UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY blocker_blocked (blocker_id,blocked_id)
    ) $charset_collate;
    ";
    dbDelta( $sql );
    add_option( 'wcfm_vchat_db_version', $wcfm_vchat_db_version );
    // Add missing indexes for performance
    $wpdb->query("ALTER TABLE $messages_table ADD INDEX idx_created_at (created_at)");
    $wpdb->query("ALTER TABLE $messages_table ADD INDEX idx_sender_receiver (sender_id, receiver_id, created_at)");
    $wpdb->query("ALTER TABLE $participants ADD INDEX idx_user_group (user_id, group_id)");
    // Create All Vendors group and add current vendors
    $all_group_id = get_option('wcfm_vchat_all_vendors_group_id');
    if ( ! $all_group_id ) {
        $wpdb->insert( $groups_table, array( 'name' => 'All Vendors', 'created_by' => 1 ) );
        $all_group_id = $wpdb->insert_id;
        if ( $all_group_id ) {
            update_option( 'wcfm_vchat_all_vendors_group_id', $all_group_id );
            // Add all current vendors - IMPROVED: Better query for WCFM vendors
            if ( function_exists( 'wcfm_get_vendors_list' ) ) {
                $users = wcfm_get_vendors_list( true, 0, 0, true ); // Get approved vendors
                $users = array_keys( $users );
            } else {
                $vendor_roles = array( 'wcfm_vendor', 'seller', 'vendor' );
                $users = get_users( array( 'role__in' => $vendor_roles, 'fields' => 'ID' ) );
            }
            foreach ( $users as $user_id ) {
                if ( wcfm_vchat_is_vendor( $user_id ) ) {
                    $wpdb->insert( $participants, array( 'group_id' => $all_group_id, 'user_id' => $user_id ), array( '%d', '%d' ) );
                }
            }
        }
    }
}
// IMPROVED: Hook to auto-add new vendors to All Vendors group - Use 'wcfm_after_vendor_approval' if available, fallback to user_register with delayed role check
add_action( 'wcfm_after_vendor_approval', 'wcfm_vchat_add_new_vendor_to_all_group' );
add_action( 'user_register', 'wcfm_vchat_delayed_add_new_vendor_to_all_group' );
function wcfm_vchat_delayed_add_new_vendor_to_all_group( $user_id ) {
    // Delayed check for role assignment (vendors might be assigned role after register)
    wp_schedule_single_event( time() + 60, 'wcfm_vchat_check_and_add_vendor', array( $user_id ) ); // Check 1 min later
}
add_action( 'wcfm_vchat_check_and_add_vendor', 'wcfm_vchat_add_new_vendor_to_all_group' );
function wcfm_vchat_add_new_vendor_to_all_group( $user_id ) {
    if ( ! wcfm_vchat_is_vendor( $user_id ) ) return;
    global $wpdb;
    $all_group_id = get_option( 'wcfm_vchat_all_vendors_group_id' );
    if ( ! $all_group_id ) return;
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants WHERE group_id = %d AND user_id = %d", $all_group_id, $user_id ) );
    if ( ! $exists ) {
        $wpdb->insert( $participants, array( 'group_id' => $all_group_id, 'user_id' => $user_id ), array( '%d', '%d' ) );
    }
}
/**
 * Check vendor status. Works with WCFM helper if available, otherwise fallback to role check.
 */
function wcfm_vchat_is_vendor( $user_id = null ) {
    if ( ! $user_id ) $user_id = get_current_user_id();
    // WCFM helper
    if ( function_exists( 'wcfm_is_vendor' ) ) {
        $user = get_userdata( $user_id );
        // wcfm_is_vendor checks current user, so simulate if necessary
        if ( $user_id == get_current_user_id() ) {
            return wcfm_is_vendor();
        } else {
            // best-effort check: role contains 'wcfm_vendor' or 'seller'
            $roles = (array) $user->roles;
            return in_array( 'wcfm_vendor', $roles ) || in_array( 'seller', $roles ) || in_array( 'vendor', $roles );
        }
    }
    $user = get_userdata( $user_id );
    if ( ! $user ) return false;
    $roles = (array) $user->roles;
    return in_array( 'wcfm_vendor', $roles ) || in_array( 'seller', $roles ) || in_array( 'vendor', $roles );
}
/**
 * Get last read time for a conversation.
 */
function wcfm_vchat_get_last_read( $user_id, $type, $convo_id ) {
    $key = 'wcfm_vchat_last_read_' . $user_id . '_' . $type . '_' . $convo_id;
    $last_read = get_transient( $key );
    return $last_read ?: '1970-01-01 00:00:00';
}
/**
 * Get preview text for conversations list, parsing attachments. UPDATED: More flexible regex without requiring [ ], handles raw "File: name (type) url".
 */
function wcfm_vchat_get_preview( $content ) {
    // Flexible regex: matches "File: name (type) url" with optional [ ] and whitespace
    if ( preg_match( '/File:\s*([^\s\)]+)\s*\(\s*(image|file)\s*\)\s*(https?:\/\/.*)?$/i', $content, $matches ) ) {
        $name = $matches[1];
        $type = $matches[2];
        $url = isset($matches[3]) ? trim($matches[3]) : '';
        if ( $url ) {
            if ( $type === 'image' ) {
                return '🖼️ Photo: ' . pathinfo( $name, PATHINFO_FILENAME );
            } else {
                return '📎 ' . pathinfo( $name, PATHINFO_FILENAME );
            }
        }
    }
    return wp_trim_words( strip_tags( $content ), 8, '...' );
}
/**
 * Get WCFM store URL for vendor (if WCFM Marketplace available) - FIXED: Prioritize wcfm_get_endpoint_url; better fallback with get_permalink structure check
 */
function wcfm_vchat_get_store_url( $user_id ) {
    if ( ! function_exists( 'wcfmmp_get_store' ) ) return '';
    $store = wcfmmp_get_store( $user_id );
    if ( ! $store || empty( $store->store_slug ) ) return '';
    // FIXED: Use wcfm_get_endpoint_url if available
    if ( function_exists( 'wcfm_get_endpoint_url' ) ) {
        $store_url = wcfm_get_endpoint_url( 'store', $store->store_slug );
        if ( $store_url ) return $store_url;
    }
    // IMPROVED Fallback: Check if store page exists, else use standard structure
    $store_page_id = get_option( 'woocommerce_myaccount_page_id', false );
    if ( $store_page_id ) {
        $store_base = get_permalink( $store_page_id ) . 'view-store/' . $store->store_slug . '/';
        return trailingslashit( $store_base );
    }
    return trailingslashit( home_url( '/store/' . $store->store_slug . '/' ) ); // Ultimate fallback
}
/**
 * Print popup HTML in footer for vendors.
 */
add_action( 'wp_footer', 'wcfm_vchat_print_popup' );
function wcfm_vchat_print_popup() {
    if ( ! is_user_logged_in() ) return;
    if ( ! wcfm_vchat_is_vendor() ) return;
    // Only show on URLs containing "store-manager"
    if ( strpos( $_SERVER['REQUEST_URI'], 'store-manager' ) === false ) return;
    // Ensure current user is in All Vendors group
    $all_group_id = get_option( 'wcfm_vchat_all_vendors_group_id' );
    if ( $all_group_id ) {
        global $wpdb;
        $participants_table = $wpdb->prefix . 'wcfm_vchat_participants';
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants_table WHERE group_id = %d AND user_id = %d", $all_group_id, get_current_user_id() ) );
        if ( ! $exists ) {
            $wpdb->insert( $participants_table, array( 'group_id' => $all_group_id, 'user_id' => get_current_user_id() ), array( '%d', '%d' ) );
        }
    }
    // Upgrade hook for indexes and schema
    $current_db_version = get_option('wcfm_vchat_db_version');
    if ($current_db_version < '1.5.29') {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
        $participants = $wpdb->prefix . 'wcfm_vchat_participants';
        $wpdb->query("ALTER TABLE $messages_table ADD INDEX idx_created_at (created_at)");
        $wpdb->query("ALTER TABLE $messages_table ADD INDEX idx_sender_receiver (sender_id, receiver_id, created_at)");
        $wpdb->query("ALTER TABLE $participants ADD INDEX idx_user_group (user_id, group_id)");
        // Upgrade content to LONGTEXT for large attachments
        $wpdb->query("ALTER TABLE $messages_table MODIFY content LONGTEXT");
        update_option('wcfm_vchat_db_version', '1.5.29');
    }
    // Localize script variables directly here for single-file reliability
    $localize_vars = array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'wcfm_vchat_nonce' ),
        'current_user_id' => get_current_user_id(),
        'all_group_id' => $all_group_id ? intval( $all_group_id ) : 0,
        // NEW: Auto-update toggle state (persistent via localStorage)
        'auto_update_active' => get_user_meta( get_current_user_id(), 'wcfm_vchat_auto_update', true ) ?: false,
        // NEW: WebSocket stub - Configure your WS server URL here (e.g., Pusher or custom Ratchet server)
        'websocket_url' => 'ws://your-websocket-server.com:8080', // Placeholder - implement server-side for full real-time
    );
    ?>
    <script>
    var wcfmVChat = <?php echo json_encode( $localize_vars ); ?>;
    </script>
    <div id="wcfm-vchat-root" aria-hidden="true">
        <div id="wcfm-vchat-button" title="Vendor Chat">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="16" y1="10" x2="8" y2="10"></line><line x1="16" y1="12" x2="8" y2="12"></line><line x1="16" y1="14" x2="8" y2="14"></line></svg>
        </div>
        <div id="wcfm-vchat-modal" class="wcfm-vchat-closed">
            <div class="wcfm-vchat-header">
                <div class="wcfm-vchat-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><line x1="16" y1="10" x2="8" y2="10"></line><line x1="16" y1="12" x2="8" y2="12"></line><line x1="16" y1="14" x2="8" y2="14"></line></svg>
                    Vendor Chat
                </div>
                <div class="wcfm-vchat-controls">
                    <button id="wcfm-vchat-minimize">_</button>
                    <button id="wcfm-vchat-fullscreen" title="Fullscreen"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" x2="14" y1="3" y2="10"></line><line x1="3" x2="10" y1="21" y2="14"></line></svg></button>
                    <button id="wcfm-vchat-close">×</button>
                </div>
            </div>
            <div class="wcfm-vchat-body">
                <div class="wcfm-vchat-left">
                    <div class="wcfm-vchat-tabs">
                        <button data-tab="individual" class="active">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus w-4 h-4 mr-1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" x2="19" y1="8" y2="14"></line><line x1="22" x2="16" y1="11" y2="11"></line></svg>
                            Individual
                        </button>
                        <button data-tab="groups">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-4 h-4 mr-1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            Groups
                        </button>
                    </div>
                    <div class="wcfm-vchat-search">
                        <div class="search-input">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                            <input type="search" id="wcfm-vchat-search" placeholder="Search vendors..." />
                        </div>
                    </div>
                    <button id="wcfm-vchat-refresh" title="Click to toggle auto-refresh">⟳</button>
                    <div id="wcfm-vchat-loading" style="display:none; text-align:center; padding:20px; color:#8E8E93;">Loading...</div>
                    <ul id="wcfm-vchat-conversations" class="wcfm-list"></ul>
                    <button id="wcfm-vchat-new-chat" title="New Chat"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square-plus w-5 h-5 text-accent"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M12 7v6"></path><path d="M9 10h6"></path></svg></button>
                </div>
                <div class="wcfm-vchat-right">
                    <div id="wcfm-vchat-chat-header" class="chat-header">
                        <button id="wcfm-vchat-back" class="back-btn" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-5 h-5"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg></button>
                        <div>Select a conversation</div>
                    </div>
                    <!-- ENHANCED: Initial empty state for no chat selected with image -->
                    <div id="wcfm-vchat-messages" class="messages">
                        <div class="no-chat-selected">
                            <img src="https://via.placeholder.com/300x200/17a2b8/ffffff?text=Start+a+Chat" alt="Chat illustration" class="no-chat-image">
                            <div class="chat-icon"></div>
                            <div class="no-chat-title"></div>
                            <div class="no-chat-subtitle"></div>
                        </div>
                    </div>
                    <div class="wcfm-vchat-input-row">
                        <button class="input-emoji">😊</button>
                        <button class="input-attach">📎</button>
                        <div id="attachments-container"></div>
                        <textarea id="wcfm-vchat-input" placeholder="Type a message..." disabled></textarea>
                        <button id="wcfm-vchat-send" disabled>➤</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="wcfm-vchat-dropdown" class="wcfm-vchat-dropdown" style="display:none;">
            <ul>
                <li data-action="clear-chat">Clear Chat</li>
                <li data-action="view-profile" style="display:none;">View Profile</li>
                <li data-action="block-vendor" style="display:none;">Block Vendor</li>
                <li data-action="add-to-all-vendors" style="display:none;">Add to All Vendors</li>
                <li data-action="view-members">View Members</li>
                <li data-action="add-members" style="display:none;">Add Members</li>
                <li data-action="exit-group" style="display:none;">Exit Group</li>
                <li data-action="delete-group" style="display:none;">Delete Group</li>
            </ul>
        </div>
    </div>
    <style>
    /* Clean UI without animations */
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    #wcfm-vchat-button {
        position: fixed; right: 50px; bottom: 0px; background: linear-gradient(135deg, #17a2b8, #05b895); color: #fff;
        width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 99999; box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3); touch-action: manipulation; /* Faster mobile taps */
    }
    #wcfm-vchat-modal {
        position: fixed; right: 20px; bottom: 80px; width: 800px; max-width: 90vw; height: 600px; max-height: 80vh;
        background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1); overflow: hidden;
        display: flex; flex-direction: column; z-index: 99999;
    }
    #wcfm-vchat-modal.fullscreen {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; max-width: none; max-height: none; border-radius: 0; margin: 0;
    }
    #wcfm-vchat-modal.chat-open .wcfm-vchat-left { display: none; }
    #wcfm-vchat-modal.chat-open .wcfm-vchat-right { flex: 1; height: 100%; }
    .wcfm-vchat-closed { display: none !important; }
    .wcfm-vchat-header {
        display: flex; justify-content: space-between; align-items: center; padding: 12px 16px;
        background: linear-gradient(135deg, #09b19c, #17a2b8); color: #fff; font-weight: 600;
    }
    .wcfm-vchat-title { display: flex; align-items: center; gap: 8px; font-size: 16px; }
    .wcfm-vchat-controls { display: flex; gap: 8px; }
    .wcfm-vchat-controls button {
        background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 4px;
        border-radius: 50%; box-shadow: 0px 0px 0px 0px rgba(0, 0, 0, 0.05); min-height: 44px; min-width: 44px; /* Mobile tap target */
        display: flex; align-items: center; justify-content: center; touch-action: manipulation;
    }
    .wcfm-vchat-controls button:hover { background: rgba(255, 255, 255, 0.2); }
    .wcfm-vchat-body { display: flex; flex: 1; overflow: hidden; background: #F2F2F7; }
    .wcfm-vchat-left { padding-right: 4px; padding-left: 4px; width: 300px; border-right: 1px solid #E5E5EA; display: flex; flex-direction: column; background: #fff; position: relative; }
    .wcfm-vchat-right { flex: 1; display: flex; flex-direction: column; background: #F2F2F7; display: none; /* Initially hidden until chat selected */ }
    .wcfm-vchat-tabs { display: flex; border-bottom: 1px solid #E5E5EA; }
    .wcfm-vchat-tabs button {
        color: #000; flex: 1; border: none; background: #f3f3f3; padding: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px;
        font-weight: 500; margin: 10px 8px; min-height: 44px; /* Mobile tap target */
        touch-action: manipulation;
    }
    .wcfm-vchat-tabs button:hover { background: #E5E5EA; }
    .wcfm-vchat-tabs .active { background: #0caea2; color: #fff; margin: 10px 8px; }
    .wcfm-vchat-search { padding: 12px; border-bottom: 1px solid #E5E5EA; position: relative; }
    .search-input { position: relative; display: flex; align-items: center; }
    .search-input svg { position: absolute; left: 12px; color: #8E8E93; }
    .wcfm-vchat-search input {
        width: 100%; padding: 10px 12px 10px 36px; border: 1px solid #E5E5EA; border-radius: 20px; background: #F2F2F7;
        font-size: 14px; border-radius: 30px !important;
    }
    .wcfm-vchat-search input:focus { border-color: #007AFF; outline: none; }
    #wcfm-vchat-refresh {
        position: absolute; top: 12px; right: 12px; background: none; border: none; color: #8E8E93; cursor: pointer; font-size: 18px; padding: 4px; border-radius: 50%; min-height: 44px; min-width: 44px; display: flex; align-items: center; justify-content: center; touch-action: manipulation; z-index: 1;
    }
    #wcfm-vchat-refresh:hover { background: rgba(0,0,0,0.1); color: #000; }
    #wcfm-vchat-new-chat {
        position: absolute; bottom: 20px; right: 16px; height: 40px; background: linear-gradient(135deg, #17a2b8, #05b895); color: #fff;
        border: none; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; transition: background 0.2s; padding-right: 10px !important;
        padding-left: 10px !important; padding-top: 10px !important; padding-bottom: 10px !important; min-height: 44px; min-width: 44px; /* Mobile tap */
        touch-action: manipulation;
    }
    #wcfm-vchat-new-chat:hover { background: #128C7E; }
    .wcfm-list { list-style: none; margin: 0; padding: 0; overflow-y: auto; flex: 1; }
    .wcfm-list li {
        position: relative; padding: 12px; border-bottom: 1px solid #E5E5EA; display: flex; gap: 12px; align-items: center; cursor: pointer;
        min-height: 60px; /* Taller rows for easier swiping/tapping */
        touch-action: manipulation;
    }
    .wcfm-list li:hover { background: #F2F2F7; }
    .wcfm-list li.has-unread { background: rgba(76, 175, 80, 0.1); }
    .wcfm-list .avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
    .wcfm-list .info { flex: 1; }
    .wcfm-list .info strong { display: block; color: #000; font-weight: 600; }
    .wcfm-list .store-name { font-size: 12px; color: #8E8E93; margin-top: 2px; cursor: pointer; text-decoration: underline; }
    .wcfm-list .preview { font-size: 14px; color: #8E8E93; margin-top: 2px; }
    .wcfm-list .members-count { font-size: 12px; color: #8E8E93; margin-top: 2px; }
    .unread-dot {
        position: absolute; top: 16px; right: 16px; width: 10px; height: 10px; background: #4CAF50; border-radius: 50%;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
        100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
    }
    .no-conversations {
        padding: 40px 20px; text-align: center; color: #8E8E93; font-style: italic; list-style: none;
    }
    .chat-header {
        padding: 12px 16px; background: #fff; border-bottom: 1px solid #E5E5EA; display: flex; align-items: center; gap: 12px;
        font-weight: 500; color: #000; position: relative; justify-content: space-between;
    }
    .chat-header .back-btn {
        background: none; border: none; color: #000; font-size: 18px; cursor: pointer; padding: 8px; min-width: 44px; min-height: 44px;
        display: flex; align-items: center; justify-content: center; touch-action: manipulation;
    }
    .chat-header .back-btn:hover { background: #F2F2F7; border-radius: 50%; }
    .chat-header .avatar { width: 40px; height: 40px; border-radius: 50%; }
    .chat-header .name { font-size: 16px; flex: 1; text-align: center; }
    .chat-header .store-link { font-size: 12px; color: #007AFF; text-decoration: underline; cursor: pointer; margin-top: 2px; }
    .chat-header .menu-btn, .chat-header .add-members-btn {
        background: none; border: none; color: #8E8E93; cursor: pointer; padding: 4px;
        border-radius: 50%; font-size: 20px; min-height: 44px; min-width: 44px; /* Mobile tap */
        touch-action: manipulation;
    }
    .chat-header .add-members-btn { font-size: 24px; color: #007AFF; }
    .chat-header .add-members-btn:hover { background: #F2F2F7; }
    /* FIXED: Dropdown now positions above menu button for desktop/mobile; higher z-index */
    .wcfm-vchat-dropdown {
        position: absolute; background: #fff; border: 1px solid #E5E5EA; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 100001; /* Increased z-index */ min-width: 150px; /* Default: below, JS will adjust to above */
    }
    .wcfm-vchat-dropdown ul { list-style: none; margin: 0; padding: 8px 0; }
    .wcfm-vchat-dropdown li {
        padding: 8px 16px; cursor: pointer; font-size: 14px; min-height: 44px; /* Mobile tap */
        display: flex; align-items: center; touch-action: manipulation;
    }
    .wcfm-vchat-dropdown li:hover { background: #F2F2F7; }
    .messages {
        flex: 1; padding: 16px; overflow-y: auto; display: flex; flex-direction: column; /* Standard column: oldest at top, newest at bottom */
        /* Removed broken var() and ur() – assuming it's a placeholder for a subtle bg pattern; add if needed */
        background-color: #F2F2F7; /* Fallback */
    }
    .messages.drag-over {
        background: rgba(0, 122, 255, 0.1);
    }
    .date-separator { text-align: center; margin: 20px 0 10px 0; font-weight: bold; color: #8E8E93; font-size: 14px; }
    .message { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 12px; }
    .message.me { flex-direction: row-reverse; }
    .message .avatar { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; }
    .message .bubble { max-width: 100%; padding: 8px 12px; border-radius: 18px; background: #007AFF; color: #fff; word-wrap: break-word; }
    .message.me .bubble { max-width: 100%; background: #E5E5EA; color: #000; border-bottom-right-radius: 4px; }
    .message.them .bubble { background: #fff; color: #000; border-bottom-left-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .message .sender { font-size: 12px; color: #8E8E93; margin-bottom: 2px; }
    .message.me .sender { display: none; }
    .message .time { font-size: 11px; color: #8E8E93; text-align: right; margin-top: 4px; }
    .wcfm-vchat-input-row {
        display: flex; align-items: flex-end; gap: 8px; padding: 12px 16px; background: #fff; border-top: 1px solid #E5E5EA; position: relative;
    }
    .wcfm-vchat-input-row button {
        background: none; border: none; color: #8E8E93; cursor: pointer; padding: 8px; border-radius: 6%;
        font-size: 20px; min-height: 44px; min-width: 44px; /* Mobile tap targets */
        touch-action: manipulation;
    }
    .wcfm-vchat-input-row button:hover { color: #000000ff; }
    .wcfm-vchat-input-row button:disabled { opacity: 0.5; cursor: not-allowed; }
    #wcfm-vchat-input {
        height: 40px; flex: 1; border: none; background: #F2F2F7; border-radius: 20px; padding: 10px 16px;
        font-size: 16px; resize: none; max-height: 100px; outline: none;
    }
    #wcfm-vchat-input:focus { background: #fff; }
    #wcfm-vchat-input:disabled { background: #F2F2F7; opacity: 0.5; cursor: not-allowed; }
    #wcfm-vchat-send {
        background: linear-gradient(135deg, #17a2b8, #05b895); color: #fff; border: none; border-radius: 50%;
        width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer;
        min-height: 44px; min-width: 44px; /* Mobile tap */
        touch-action: manipulation;
    }
    #wcfm-vchat-send:hover:not(:disabled) { background: #000000ff; }
    #attachments-container {
        display: flex; gap: 4px; align-items: flex-end; flex-wrap: wrap;
    }
    .attachment-preview {
        background: #E5E5EA; padding: 8px; border-radius: 12px; display: flex; align-items: center; gap: 8px;
        max-width: 200px; font-size: 14px; color: #000;
    }
    .attachment-preview img {
        max-width: 60px; max-height: 60px; border-radius: 8px; object-fit: cover;
    }
    .remove-attachment {
        cursor: pointer; margin-left: 5px; color: #FF3B30; font-weight: bold; font-size: 18px; line-height: 1;
    }
    .remove-attachment:hover { color: #D32F2F; }
    #emoji-picker {
        display:none; position:absolute; bottom:60px; left:10px; background:white; border:1px solid #E5E5EA;
        border-radius:8px; padding:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); z-index:10; max-width:200px; max-height:200px;
        overflow-y:auto; display:flex; flex-wrap:wrap; gap:4px;
    }
    #emoji-picker button {
        background:none; border:none; font-size:20px; cursor:pointer; padding:4px; margin:2px; border-radius:4px;
        width:32px; height:32px; text-align:center; flex: 0 0 auto; min-height: 44px; min-width: 44px; /* Mobile emoji taps */
        touch-action: manipulation;
    }
    #emoji-picker button:hover { background:#F2F2F7; }
    .attach-options {
        position: absolute; bottom: 60px; left: 10px; background: #fff; border: 1px solid #E5E5EA; border-radius: 8px;
        padding: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10;
    }
    .attach-options button {
        display: block; width: 100%; padding: 8px; border: none; background: none; text-align: left; cursor: pointer;
        font-size: 14px; min-height: 44px; /* Mobile tap */
        touch-action: manipulation;
    }
    .attach-options button:hover { background: #F2F2F7; }
    /* Skeleton loader */
    .skeleton { background: #E5E5EA; height: 72px; margin: 8px; border-radius: 8px; animation: pulse 1.5s ease-in-out infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    /* Thin scrollbars for chat messages and conversations list - Cross-browser */
    .messages,
    .wcfm-list {
        scrollbar-width: thin;
        scrollbar-color: #999 #f1f1f1;
        -webkit-overflow-scrolling: touch; /* Native iOS momentum scrolling */
    }
    .messages::-webkit-scrollbar,
    .wcfm-list::-webkit-scrollbar {
        width: 6px;
    }
    .messages::-webkit-scrollbar-track,
    .wcfm-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .messages::-webkit-scrollbar-thumb,
    .wcfm-list::-webkit-scrollbar-thumb {
        background: #999;
        border-radius: 3px;
    }
    .messages::-webkit-scrollbar-thumb:hover,
    .wcfm-list::-webkit-scrollbar-thumb:hover {
        background: #666;
    }
    /* ENHANCED: Empty state for no chat selected with image */
    .no-chat-selected {
        display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: #8E8E93; padding: 40px 20px;
    }
    .no-chat-selected .no-chat-image {
        width: 200px; height: auto; max-width: 100%; margin-bottom: 24px; border-radius: 12px; opacity: 0.8;
    }
    .no-chat-selected .chat-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
    .no-chat-selected .no-chat-title { font-size: 18px; font-weight: 500; margin-bottom: 8px; }
    .no-chat-selected .no-chat-subtitle { font-size: 14px; opacity: 0.7; }
    /* Existing no-messages for selected chat with no msgs */
    .no-messages { text-align: center; color: #8E8E93; padding: 40px 20px; font-style: italic; }
    /* Responsive - ENHANCED for mobile: Bottom-sheet style, better keyboard handling, full-width new chat popup; added tablet support */
    @media (max-width: 1024px) and (min-width: 769px) {
        #wcfm-vchat-modal { width: 95vw; height: 85vh; }
        .wcfm-vchat-left { width: 35%; }
    }
    @media (max-width: 768px) {
        #wcfm-vchat-modal {
            right: 10px; left: 10px; width: auto; height: 90vh; bottom: 0; border-radius: 20px 20px 0 0;
            /* Bottom-sheet style: slides up from bottom */
        }
        .wcfm-vchat-left {
            width: 100%; height: auto; order: 1; /* Stack conversations above chat */
            padding: 8px; /* Reduced padding for more space */
        }
        .wcfm-vchat-right {
            order: 2; flex: none; height: 50vh; /* Fixed height for chat area to prevent overgrowth */
        }
        .wcfm-vchat-body { flex-direction: column; height: 100vh; }
        #wcfm-vchat-new-chat { bottom: 20px; right: 16px; }
        .wcfm-list li {
            padding: 8px; gap: 8px; min-height: 56px; /* Compact but tappable rows */
            font-size: 14px; /* Smaller text */
        }
        .wcfm-list .avatar { width: 40px; height: 40px; } /* Smaller avatars */
        .wcfm-list .members-count { display: none; } /* Hide on mobile to save space */
        .unread-dot { top: 12px; right: 12px; width: 8px; height: 8px; }
        .wcfm-vchat-tabs button {
            padding: 8px 4px; font-size: 14px; margin: 5px 4px; /* Compact tabs */
        }
        .wcfm-vchat-search input {
            padding: 8px 8px 8px 32px; font-size: 16px; /* Mobile-optimized input size */
        }
        #wcfm-vchat-refresh { top: 20px; right: 8px; font-size: 16px; }
        .messages { padding: 8px; gap: 8px; } /* Tighter message spacing */
        .message .avatar { width: 28px; height: 28px; } /* Smaller avatars in chat */
        .message .bubble { padding: 6px 10px; font-size: 15px; } /* Readable on small screens */
        #wcfm-vchat-input {
            font-size: 16px; /* Prevents zoom on iOS */
            height: 44px; /* Taller for easier typing */
        }
        .no-chat-selected .no-chat-image { width: 150px; margin-bottom: 16px; } /* Smaller image on mobile */
        #emoji-picker {
            max-height: 150px; max-width: 100%; left: 0; right: 0; /* Full-width on mobile */
            grid-template-columns: repeat(auto-fit, minmax(40px, 1fr)); /* Grid for better emoji layout */
            display: grid !important; /* Override flex for grid */
        }
        #emoji-picker button { width: 40px; height: 40px; } /* Larger emoji buttons */
        /* Landscape support: Reduce heights further */
        @media (max-width: 768px) and (orientation: landscape) {
            #wcfm-vchat-modal { height: 100vh; }
            .wcfm-vchat-right { height: 60vh; }
        }
        /* ENHANCED: Full-screen new chat popup on mobile (centered, larger) */
        .new-chat-popup-mobile {
            position: fixed !important; top: 65% !important; left: 50% !important; transform: translate(-50%, -50%) !important;
            right: auto !important; bottom: auto !important; width: 85vw !important; max-width: 400px !important; height: auto !important;
            max-height: 50vh !important; border-radius: 16px !important;
        }
        .new-chat-popup-mobile h3 { text-align: center; margin-bottom: 16px; font-size: 18px; }
        .new-chat-popup-mobile div[data-id] { min-height: 56px; padding: 12px; } /* Larger tap targets */
        /* FIXED: Mobile dropdown positioning - fixed right-aligned, but JS handles above */
        .wcfm-vchat-dropdown { position: fixed !important; left: auto !important; right: 10px !important; top: auto !important; width: 160px !important; }
    }
    /* NEW: Styles for store links in group members popup */
    .store-link { color: #007AFF; text-decoration: underline; cursor: pointer; font-size: 12px; }
    .store-link:hover { color: #0056b3; }
    div#wcfm-vchat-messages {
    border-radius: 0px 0px 0px 0px;
    --wpr-bg-8d69a0e4-97ec-4c77-9efb-e75eccc262df: ur(<a href="https://joomdev.com/wp-content/plugins/misc-customization/assets/images/whatsapp-bg.png" target="_blank" rel="noopener noreferrer nofollow"></a>);
    background-image: var(--wpr-bg-8d69a0e4-97ec-4c77-9efb-e75eccc262df); }
    .upload-progress { color: #8E8E93; font-size: 12px; }
    </style>
    <script>
    (function($){
        var polling_messages = null;
        var polling_list = null;
        var currentConversation = null;
        var currentLastId = 0; // Track last message ID for incremental fetches
        var isRefreshing = false; // Flag to prevent overlapping fetches
        var emojiPicker = null;
        var attachPicker = null;
        var isFullscreen = false;
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent); // Detect mobile
        var currentEmojiPage = 0;
        var emojisPerPage = isMobile ? 20 : 50; // Paginate for mobile
        var isFileSelecting = false; // Flag to prevent modal close during file selection
        var currentStoreUrl = ''; // For view profile
        var ws = null; // WebSocket connection
        var autoUpdateActive = wcfmVChat.auto_update_active; // NEW: Init from server/localized
        // FIXED: Expanded emoji pages (was truncated)
        var emojiPages = [
            [// Smileys & Emotion
                    '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','🙁','☹️','😕','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','💫','😴','😪','😵','😵‍💫','😷','🤒','🤕','🤢','🤮','🤧','😇','🤠','🥸','🤡','👺','👹','👻','💀','☠️','👽','👾','🤖','🎃','👋','🤚','🖐️','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦿','🦵','🦶','👂','🦻','👃','🧠','🫀','🫁','🦷','🦴','👀','👁️','👅','👄','💋','🩷','🧡','💛','💚','💙','🩵','💜','🖤','🤎','🩶','🤍','🧡','💔','❣️','❤️','🧡','💛','💚','💙','💜','🖤','🤍', '🐵','🐒','🦍','🦧','🐶','🐕','🐩','🐺','🦊','🦝','🐱','🐈','🦁','🐯','🐅','🐆','🐴','🐎','🦄','🦓','🦌','🐮','🐂','🐃','🐷','🐖','🐗','🐽','🐏','🐑','🐐','🐪','🐫','🦙','🦒','🐘','🦣','🐭','🐁','🐀','🐹','🐰','🐇','🐿️','🦫','🦔','🦇','🐻','🐨','🐼','🦥','🦦','🦨','🦘','🦡','🐾','🦃','🐔','🐓','🐣','🐤','🐥','🐦','🐧','🕊️','🦅','🦉','🦇','🦤','🪶','🪹','🦋','🐛','🐜','🐝','🐞','🦗','🪲','🪳','🐺','🦟','🦂','🕷️','🕸️','🦎','🐍','🐲','🐉','🦕','🦖','🐳','🐋','🐬','🐟','🐠','🐡','🦈','🐙','🦑','🦦','🦪','🐚','🪸','🦐','🦞','🦀','🐡','🌸','💐','🌺','🌻','🌼','🪷','🌷','🪻','🌺','🌹','🥀','🌸','🌺','🌻','🌼','🌽','🌾','🌱','🌲','🌳','🌴','🌵','🌾','🪵','🌰','🧄','🧅','🥑','🫘','🌶️','🫑','🥒','🥬','🥦','🧂','🫒','🍓','🫐','🍒','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥔','🧀','🥚','🍳','🧈','🧂','🥓','🍖','🍗','🥩','🥪','🌭','🍔','🍟','🍕','🌮','🌯','🫔','🥙','🧆','🌱','🫙','🦪','🍝','🍜','🍲','🍛','🍣','🍱','🥘','🍡','🍧','🍨','🍦','🥧','🍰','🎂','🍮','🍥','❄️','🥶','🧊','🔥','💨','🌪️','🌈','☀️','🌤️','⛅','🌥️','☁️','🌦️','🌧️','⛈️','🌩️','⚡','☔','💧','❄️','☃️','⛄','🌨️','☔','💦','🌊','🌊','⌚','⏰','🕰️','🧭','🧭','🎒','👓','🕶️','🥽','⛑️','🩹','🩺','🧬','🧪','🧫','🦠','💰','🪙','💳','💎','⚖️','🩺','🔧','🔨','⚒️','⛏️','🛠️','🗜️','⚙️','🧰','🧲','⚗️','🛡️','⚔️','🔮','🕯️','💡','🔦','🕳️','📱','📲','💻','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮','📱','📲','💻','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮','📱','📲','💻','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮','🔋','🔌','💡','🔦','🛜','🛰️','📡','🖨️','⚙️','🔧','🔧','🔨','⚒️','⛏️','🛠️','🗜️','⚙️','🧰','🧲','⚗️','🛡️','⚔️','🔮','🕯️','💡','🔦','🕳️','📱','📲','💻','⌨️','🖥️','🖨️','🖱️','🖲️','🕹️','🗜️','💽','💾','💿','📀','🧮'], // Page 1: Basics
            ['😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','🙁','☹️','😕','😣'], // Page 2
            ['👋','🤚','🖐️','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍'], // Page 3: Hands
            ['👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🙏','✍️','💪','🦾','🦿','🧠','🫀','🫁','🦷','🦴','👀'] // Page 4: More
        ];
        $(document).ready(function() {
            // NEW: Persist auto-update toggle in localStorage for session
            if (localStorage.getItem('wcfm_vchat_auto_update') !== null) {
                autoUpdateActive = JSON.parse(localStorage.getItem('wcfm_vchat_auto_update'));
            }
            // NEW: WebSocket setup (stub - connect on open, send/receive for real-time updates)
            function initWebSocket() {
                if (wcfmVChat.websocket_url && wcfmVChat.websocket_url !== 'ws://your-websocket-server.com:8080') {
                    ws = new WebSocket(wcfmVChat.websocket_url);
                    ws.onopen = function() {
                        ws.send(JSON.stringify({type: 'join', user_id: wcfmVChat.current_user_id}));
                        console.log('WebSocket connected');
                    };
                    ws.onmessage = function(event) {
                        var data = JSON.parse(event.data);
                        if (data.type === 'new_message' || data.type === 'new_image') {
                            autoUpdateActive = true;
                            if (currentConversation && (data.convo_type === currentConversation.type && data.convo_id === currentConversation.id)) {
                                fetchMessages(currentConversation.type, currentConversation.id, currentLastId, true); // Incremental update
                            }
                            refreshConversationsList(); // Update list if new
                            autoUpdateActive = false;
                        }
                    };
                    ws.onclose = function() {
                        console.log('WebSocket closed - falling back to polling');
                        // Fallback to polling if WS fails
                    };
                }
            }
            // ENHANCED: Function to show no-chat-selected state (with image)
            function showNoChatSelected() {
                $('#wcfm-vchat-messages').html('<div class="no-chat-selected"><div class="chat-icon"></div><div class="no-chat-title"></div><div class="no-chat-subtitle"></div></div>');
                $('#wcfm-vchat-input').prop('disabled', true).val('');
                $('#wcfm-vchat-send').prop('disabled', true);
                currentLastId = 0; // Reset on new chat
                clearAttachment();
            }
            // ENHANCED: Function to enable input for selected chat
            function enableChatInput() {
                $('#wcfm-vchat-input').prop('disabled', false);
                $('#wcfm-vchat-send').prop('disabled', false);
            }
            function clearAttachment() {
                $('#attachments-container').empty();
                delete window.currentAttachment;
            }
            function bindRemoveAttachment() {
                $('.remove-attachment').off('click').on('click', function(e) {
                    e.stopPropagation();
                    clearAttachment();
                });
            }
            // ENHANCED: Show empty conversations message
            function showEmptyConversations() {
                $('#wcfm-vchat-conversations').html('<li class="no-conversations">No conversations yet. Start a new one!</li>');
            }
            // ENHANCED: Show error in conversations
            function showConversationsError() {
                $('#wcfm-vchat-conversations').html('<li class="no-conversations">Error loading conversations. Please refresh.</li>');
            }
            // UPDATED: Parse attachment content (more flexible regex to capture "File: name (type) url" without requiring [ ], handles whitespace)
            function parseAttachment(content) {
                // Flexible regex: matches "File: name (type) url"
                var match = content.match(/File:\s*([^\s\)]+)\s*\(\s*(image|file)\s*\)\s*(https?:\/\/.*)?$/i);
                if (match) {
                    var name = match[1];
                    var type = match[2];
                    var rest = match[3] ? match[3].trim() : '';
                    if (rest) {
                        var url = rest;
                        // FIXED: Ensure absolute URL (in case relative)
                        if (url.indexOf('http') !== 0) {
                            url = '<?php echo home_url(); ?>' + (url.startsWith('/') ? '' : '/') + url;
                        }
                        var fileName = name.replace(/\.[^/.]+$/, ""); // Remove extension
                        if (type === 'image') {
                            return { html: '<img src="' + url + '" alt="' + name + '" style="max-width:200px; border-radius:8px; margin:4px 0;" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"><small style="color:#8E8E93; display:none;">🖼️ Image: ' + fileName + '</small>' };
                        } else {
                            return { html: '📎 <a href="' + url + '" target="_blank" download="' + name + '">' + fileName + '</a> <small style="color:#8E8E93;">(download)</small>' };
                        }
                    }
                }
                // Fallback for plain text or old base64 (deprecated)
                var oldMatch = content.match(/\[File:\s*([^\]]+)\]\s*(data:([^;]+);base64,([A-Za-z0-9+/=]+))/i);
                if (oldMatch) {
                    var fileName = oldMatch[1];
                    var mimeType = oldMatch[3];
                    var base64Data = oldMatch[4];
                    var fileData = 'data:' + mimeType + ';base64,' + base64Data;
                    if (mimeType.startsWith('image/')) {
                        return { html: '<img src="' + fileData + '" alt="' + fileName + '" style="max-width:200px; border-radius:8px; margin:4px 0;" onerror="this.style.display=\'none\';">' };
                    } else {
                        return { html: '📎 ' + fileName + ' <small>(File - download not supported)</small>' };
                    }
                }
                return { html: content.replace(/<[^>]*>/g, '') }; // Strip tags
            }
            function sendContent(content, isOptimistic = true) {
                if (isOptimistic) {
                    var timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                    var parsed = parseAttachment(content);
                    var bubbleContent = parsed.html;
                    var messageHtml = '<div class="message me optimistic"><div class="avatar" style="background:#E5E5EA;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#8E8E93;">Y</div><div><div class="bubble">' + bubbleContent + '</div><div class="time">' + timeStr + '</div></div></div>';
                    var box = $('#wcfm-vchat-messages');
                    box.append(messageHtml);
                    box.scrollTop(box[0].scrollHeight);
                }
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_send_message', nonce: wcfmVChat.nonce, type: currentConversation.type, id: currentConversation.id, content: content }, function(resp) {
                    if (isOptimistic) {
                        $('#wcfm-vchat-messages .optimistic:last').removeClass('optimistic');
                    }
                    if (!resp || resp.error) {
                        if (isOptimistic) {
                            $('#wcfm-vchat-messages .message:last').remove();
                        }
                        alert('Send failed: ' + (resp ? resp.data : 'Unknown error'));
                    } else {
                        // NEW: Trigger WS send if connected (for real-time broadcast)
                        if (ws && ws.readyState === WebSocket.OPEN) {
                            ws.send(JSON.stringify({type: 'message_sent', convo_type: currentConversation.type, convo_id: currentConversation.id, content: content}));
                        }
                        refreshConversationsList(); // OPTIMIZED: Refresh list only after successful send
                        // FIXED: No timeout reset - let toggle handle persistence
                    }
                }, 'json').fail(function() {
                    if (isOptimistic) {
                        $('#wcfm-vchat-messages .message:last').remove();
                    }
                });
            }
            // Initial state
            showNoChatSelected();
            initWebSocket(); // NEW: Init WS on load
            // REMOVED: Preload logic for simplicity - always fetch fresh
            // Mobile: Resize modal on virtual keyboard show/hide (enhanced)
            if (isMobile) {
                var initialHeight = window.innerHeight;
                $(window).on('resize orientationchange', function() { // Added orientationchange
                    var newHeight = window.innerHeight;
                    if (newHeight < initialHeight * 0.9) { // Keyboard likely open
                        $('#wcfm-vchat-modal').css('height', newHeight + 'px');
                    } else {
                        $('#wcfm-vchat-modal').css('height', '90vh');
                    }
                });
                $('#wcfm-vchat-input').on('focus', function() {
                    setTimeout(() => ($('#wcfm-vchat-messages').scrollTop($('#wcfm-vchat-messages')[0].scrollHeight)), 300); // FIXED: Scroll to bottom after keyboard
                });
                // FIXED: Prevent iOS zoom on focus
                $('input, textarea').on('focus', function() { document.body.style.zoom = '0.99'; });
                $('input, textarea').on('blur', function() { document.body.style.zoom = ''; });
            }
            // OPTIMIZED Polling: Separate intervals for messages (fast, on chat open) and list (slow, or on events)
            function startPolling() {
                // Clear existing
                if (polling_messages) clearInterval(polling_messages);
                if (polling_list) clearInterval(polling_list);
                // Messages polling: Always active when chat open, faster if autoUpdateActive
                var messageInterval = autoUpdateActive ? 3000 : 10000; // 3s active, 10s default
                polling_messages = setInterval(function() {
                    if (currentConversation && !isRefreshing) {
                        fetchMessages(currentConversation.type, currentConversation.id, currentLastId, true); // Incremental
                    }
                }, messageInterval);
                // List polling: Slow background refresh every 60s, regardless of chat state
                polling_list = setInterval(function() {
                    refreshConversationsList();
                }, 60000); // 1 minute
                // Also refresh list if no chat selected (but less often, every 30s when no chat)
                if (!currentConversation) {
                    setTimeout(refreshConversationsList, 30000); // Initial refresh after 30s if no chat
                }
            }
            function stopPolling() {
                if (polling_messages) clearInterval(polling_messages);
                if (polling_list) clearInterval(polling_list);
                polling_messages = null;
                polling_list = null;
            }
            // Modified button click to toggle open/close
            $('#wcfm-vchat-button').on('click', function() {
                var modal = $('#wcfm-vchat-modal');
                if (modal.hasClass('wcfm-vchat-closed')) {
                    // Open
                    modal.removeClass('wcfm-vchat-closed chat-open').css('display', 'flex');
                    $('#wcfm-vchat-root').attr('aria-hidden', 'false');
                    $('#wcfm-vchat-back').hide();
                    $('#wcfm-vchat-left').show();
                    $('.wcfm-vchat-right').hide(); // Ensure right is hidden on open
                    loadConversations('individual');
                    startPolling(); // OPTIMIZED: Start optimized polling on open
                    // ENHANCED: Ensure no-chat state when opening
                    showNoChatSelected();
                    if (isMobile) $('#wcfm-vchat-input').attr('aria-label', 'Type message (mobile optimized)'); // ARIA for screen readers
                } else {
                    // Close
                    modal.removeClass('chat-open').addClass('wcfm-vchat-closed').hide();
                    $('#wcfm-vchat-root').attr('aria-hidden', 'true');
                    $('#wcfm-vchat-back').hide();
                    $('#wcfm-vchat-left').show();
                    $('.wcfm-vchat-right').hide();
                    stopPolling();
                    if (ws) ws.close(); // Close WS on modal close
                }
            });
            // FIXED: Close on outside click - Exclude new chat popup (win) from closing modal - Use selector string for closest
            // ENHANCED: Also exclude during file selection to prevent premature close
            $(document).on('click', function(e) {
                var modal = $('#wcfm-vchat-modal');
                var button = $('#wcfm-vchat-button');
                if (!modal.hasClass('wcfm-vchat-closed') && !$(e.target).closest(modal).length && !$(e.target).closest(button).length && !$(e.target).closest('.new-chat-popup').length && !isFileSelecting) {
                    // Close modal if click is outside (now excludes new chat popup using selector)
                    modal.removeClass('chat-open').addClass('wcfm-vchat-closed').hide();
                    $('#wcfm-vchat-root').attr('aria-hidden', 'true');
                    $('#wcfm-vchat-back').hide();
                    $('#wcfm-vchat-left').show();
                    $('.wcfm-vchat-right').hide();
                    stopPolling();
                    if (ws) ws.close();
                }
            });
            $('#wcfm-vchat-close').on('click', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                $('#wcfm-vchat-modal').removeClass('chat-open').addClass('wcfm-vchat-closed').hide();
                $('#wcfm-vchat-root').attr('aria-hidden', 'true');
                $('#wcfm-vchat-back').hide();
                $('#wcfm-vchat-left').show();
                $('.wcfm-vchat-right').hide();
                stopPolling();
                if (ws) ws.close();
            });
            $('#wcfm-vchat-minimize').on('click', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                var modal = $('#wcfm-vchat-modal');
                if (modal.hasClass('wcfm-vchat-closed')) {
                    modal.removeClass('wcfm-vchat-closed').css('display', 'flex');
                } else {
                    modal.addClass('wcfm-vchat-closed').hide();
                }
            });
            $('#wcfm-vchat-fullscreen').on('click', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                isFullscreen = !isFullscreen;
                $('#wcfm-vchat-modal').toggleClass('fullscreen', isFullscreen);
                // FIXED: Toggle icon properly (was duplicated SVG)
                $(this).html(isFullscreen ? '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 3 3 3 3 9"></polyline><polyline points="21 15 15 15 15 21"></polyline><line x1="3" x2="10" y1="9" y2="16"></line><line x1="21" x2="14" y1="15" y2="22"></line></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"></polyline><polyline points="9 21 3 21 3 15"></polyline><line x1="21" x2="14" y1="3" y2="10"></line><line x1="3" x2="10" y1="21" y2="14"></line></svg>');
            });
            // Back button handler for mobile - use delegation for dynamic recreation
            $(document).on('click', '#wcfm-vchat-back', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                $('.wcfm-vchat-right').hide();
                $('#wcfm-vchat-modal').removeClass('chat-open');
                $(this).hide();
                $('#wcfm-vchat-left').show();
                currentConversation = null;
                currentStoreUrl = '';
                $('#wcfm-vchat-chat-header .name').html('Select a conversation');
                // ENHANCED: Show no-chat state on back
                showNoChatSelected();
                stopPolling();
                startPolling(); // OPTIMIZED: Restart polling for list updates
            });
            function showLoading() {
                $('#wcfm-vchat-loading').html('<div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>').show();
                $('#wcfm-vchat-conversations').hide();
            }
            function hideLoading() {
                $('#wcfm-vchat-loading').hide();
                $('#wcfm-vchat-conversations').show();
            }
            // FIXED: Always fetch fresh for reliable loading, including groups
            function loadConversations(tab) {
                if (isRefreshing) return;
                isRefreshing = true;
                showLoading();
                $('.wcfm-vchat-tabs button').removeClass('active');
                $('.wcfm-vchat-tabs button[data-tab="' + tab + '"]').addClass('active');
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_get_conversations', nonce: wcfmVChat.nonce, tab: tab }, function(resp) {
                    hideLoading();
                    if (!resp || resp.error) {
                        console.error('Load conversations error:', resp); // ADDED: Debug log
                        showConversationsError();
                        isRefreshing = false;
                        return;
                    }
                    renderConversationsList(resp.data, tab);
                    isRefreshing = false;
                }, 'json').fail(function(jqXHR, textStatus, error) {
                    hideLoading();
                    console.error('AJAX fail loadConversations:', textStatus, error); // ADDED: Debug
                    showConversationsError();
                    isRefreshing = false;
                });
            }
            // Render function for DRY
            function renderConversationsList(data, tab) {
                var list = $('#wcfm-vchat-conversations').empty();
                if (!data || data.length === 0) {
                    showEmptyConversations();
                    return;
                }
                data.forEach(function(item) {
                    var li = $('<li>').attr('data-id', item.id).attr('data-type', item.type);
                    var avatar = item.avatar ? '<img src="' + item.avatar + '" class="avatar" alt="">' : '<div class="avatar" style="background:#E5E5EA; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; color:#8E8E93;">' + (item.title.charAt(0) || '?') + '</div>';
                    var previewHtml = (item.preview ? '<div class="preview">' + item.preview + '</div>' : '');
                    var storeHtml = item.store_name ? '<div class="store-name" data-store-url="' + (item.store_url || '') + '">' + item.store_name + '</div>' : '';
                    if (item.type === 'group') {
                        previewHtml += '<div class="members-count">' + item.member_count + ' members</div>';
                    } else {
                        previewHtml = storeHtml + previewHtml;
                    }
                    // ENHANCED: Add unread dot if unread_count > 0
                    if (item.unread_count > 0) {
                        li.addClass('has-unread');
                        li.append('<span class="unread-dot"></span>');
                    }
                    li.html(avatar + '<div class="info"><strong>' + item.title + '</strong>' + previewHtml + '</div>');
                    li.on('click', function() { selectConversation(item.type, item.id, item.title, item.avatar, item.member_count || 0, item.store_name || '', item.store_url || ''); });
                    list.append(li);
                });
                // ENHANCED: Bind store name clicks after rendering
                $('.store-name').off('click').on('click', function(e) {
                    e.stopPropagation();
                    var storeUrl = $(this).data('store-url');
                    if (storeUrl) {
                        window.open(storeUrl, '_blank');
                    }
                });
            }
            function refreshConversationsList() {
                if (!isRefreshing) loadConversations($('.wcfm-vchat-tabs button.active').data('tab') || 'individual');
            }
            // FIXED: Refresh button with auto-refresh toggle - Persist in localStorage and sync with server on toggle
            $('#wcfm-vchat-refresh').on('click', function(e) {
                e.stopPropagation();
                refreshConversationsList();
                autoUpdateActive = !autoUpdateActive; // Toggle auto-update
                localStorage.setItem('wcfm_vchat_auto_update', JSON.stringify(autoUpdateActive)); // Persist
                $(this).attr('title', autoUpdateActive ? 'Auto-refresh active (3s message polling)' : 'Click to enable auto-refresh');
                // NEW: Sync with server for persistence across sessions
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_toggle_auto_update', nonce: wcfmVChat.nonce, active: autoUpdateActive });
                if (currentConversation) {
                    stopPolling();
                    startPolling(); // Restart with new message interval
                }
            });
            var searchTimeout;
            $('#wcfm-vchat-search').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    // Implement client-side filter if preloaded, or trigger server search
                    var q = $('#wcfm-vchat-search').val().toLowerCase();
                    $('#wcfm-vchat-conversations li').each(function() {
                        var title = $(this).find('strong').text().toLowerCase();
                        $(this).toggle(title.includes(q));
                    });
                }, 300);
            });
            // FIXED: New chat popup - Use selector for closest to prevent modal close on selection
            $('#wcfm-vchat-new-chat').on('click', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                var tab = $('.wcfm-vchat-tabs button.active').data('tab') || 'individual';
                var q = $('#wcfm-vchat-search').val() || '';
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_search_vendors', nonce: wcfmVChat.nonce, q: q }, function(resp) {
                    if (!resp || resp.error) return;
                    var win = $('<div class="new-chat-popup" style="position:fixed;right:20px;bottom:80px;width:300px;background:#fff;box-shadow:0 20px 60px rgba(0,0,0,0.1);border-radius:20px;padding:16px;z-index:100000;max-height:400px;overflow-y:auto;">'); // Added class and inline style for reliability
                    var html = '';
                    if (tab === 'individual') {
                        html = '<div><h3>Select Vendor</h3>';
                        resp.data.forEach(function(v) {
                            var avatar = v.avatar ? '<img src="' + v.avatar + '" style="width:40px;height:40px;border-radius:50%;margin-right:12px;">' : '<div style="width:40px;height:40px;border-radius:50%;background:#E5E5EA;margin-right:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + v.display_name.charAt(0) + '</div>';
                            html += '<div style="display:flex;align-items:center;padding:12px;border-bottom:1px solid #E5E5EA;cursor:pointer;" data-id="' + v.ID + '">' + avatar + '<div><strong>' + v.display_name + '</strong><div style="font-size:12px;color:#8E8E93;">' + v.store_name + '</div></div></div>';
                        });
                        html += '</div>';
                        win.html(html);
                        // FIXED: stopPropagation to prevent bubbling to document click
                        win.on('click', '[data-id]', function(e) {
                            e.stopPropagation(); // Prevent bubbling
                            e.preventDefault(); // Extra safety
                            var to = $(this).data('id');
                            var name = $(this).find('strong').text();
                            startIndividualChat(to, name);
                            win.remove();
                        });
                    } else {
                        html = '<div><h3>Create Group</h3><input type="text" id="group-name" placeholder="Group Name" style="width:100%; padding:8px; margin-bottom:8px; border:1px solid #E5E5EA; border-radius:4px; box-sizing:border-box;"><div style="max-height:200px; overflow-y:auto;">';
                        resp.data.forEach(function(v) {
                            var avatar = v.avatar ? '<img src="' + v.avatar + '" style="width:40px;height:40px;border-radius:50%;margin-right:12px;">' : '<div style="width:40px;height:40px;border-radius:50%;background:#E5E5EA;margin-right:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + v.display_name.charAt(0) + '</div>';
                            html += '<label style="display:flex; align-items:center; padding:8px; border-bottom:1px solid #E5E5EA; cursor:pointer;"><input type="checkbox" data-id="' + v.ID + '" style="margin-right:8px;">' + avatar + '<div><strong>' + v.display_name + '</strong><div style="font-size:12px;color:#8E8E93;">' + v.store_name + '</div></div></label>';
                        });
                        html += '</div><button id="create-group-btn" style="width:100%; padding:10px; background:#007AFF; color:#fff; border:none; border-radius:4px; margin-top:8px; cursor:pointer; font-weight:500;">Create Group</button></div>';
                        win.html(html);
                        // FIXED: stopPropagation for group creation button
                        win.on('click', '#create-group-btn', function(e) {
                            e.stopPropagation(); // Prevent bubbling
                            e.preventDefault(); // Extra safety
                            var name = $('#group-name', win).val().trim();
                            var checked = [];
                            win.find('input[type="checkbox"]:checked').each(function() { checked.push($(this).data('id')); });
                            if (!name || checked.length < 1) { alert('Group name and at least one member required.'); return; }
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_create_group_with_members', nonce: wcfmVChat.nonce, name: name, members: checked }, function(resp) {
                                if (resp && resp.success) {
                                    selectConversation('group', resp.data.id, resp.data.name, '', resp.data.member_count || checked.length + 1, '', '');
                                    win.remove();
                                    refreshConversationsList();
                                } else {
                                    alert('Error creating group.');
                                }
                            }, 'json');
                        });
                    }
                    $('body').append(win);
                    // ENHANCED: On mobile, apply centered full-screen style
                    if (isMobile) {
                        win.addClass('new-chat-popup-mobile');
                    }
                    // FIXED: Use .one('click') but ensure it doesn't interfere with selections
                    $(document).one('click.newchat', function(e) { // Namespace to avoid conflicts
                        if (!$(e.target).closest(win).length) {
                            win.remove();
                        }
                    });
                }, 'json');
            });
            function startIndividualChat(user_id, name) {
                selectConversation('individual', user_id, name);
            }
            // UPDATED: Chain mark_read before fetchMessages to hide dot instantly on select; added store_name and store_url params - FIXED: Always fetch store_url
            function selectConversation(type, id, title, avatar = '', member_count = 0, store_name = '', store_url = '') {
                // FIXED: Ensure store_url is fetched if not provided (for consistency)
                if (type === 'individual' && !store_url) {
                    $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_get_store_url_ajax', nonce: wcfmVChat.nonce, user_id: id }, function(resp) {
                        if (resp && resp.success) {
                            store_url = resp.data;
                            currentStoreUrl = store_url; // Update
                            // Re-render header with store link
                            var storeLinkHtml = store_name ? '<div class="store-link" data-store-url="' + store_url + '">' + store_name + '</div>' : '';
                            $('#wcfm-vchat-chat-header .name').after(storeLinkHtml);
                            bindStoreLinkClick(); // Re-bind
                        }
                    }, 'json');
                }
                currentConversation = { type: type, id: id };
                currentStoreUrl = store_url; // NEW
                currentLastId = 0; // Reset for new chat
                var header = $('#wcfm-vchat-chat-header');
                var headerAvatar = avatar ? '<img src="' + avatar + '" class="avatar" alt="">' : '<div class="avatar" style="background:#E5E5EA;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + title.charAt(0) + '</div>';
                var groupInfo = type === 'group' ? (' (' + member_count + ' members)') : '';
                var storeLinkHtml = (type === 'individual' && store_name && store_url) ? '<div class="store-link" data-store-url="' + store_url + '">' + store_name + '</div>' : '';
                var addMembersBtn = type === 'group' ? '<button class="add-members-btn" title="Add Members">+</button>' : '';
                var menuBtn = '<button class="menu-btn">⋯</button>';
                header.html('<button id="wcfm-vchat-back" class="back-btn" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left w-5 h-5"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg></button><div style="display:flex;align-items:center;gap:12px;"><div>' + headerAvatar + '</div><div class="name">' + title + groupInfo + '</div>' + storeLinkHtml + '</div>' + addMembersBtn + menuBtn);
                // FIXED: Force show right panel with correct display for flex
                $('.wcfm-vchat-right').css('display', 'flex');
                // ENHANCED: Enable input when chat selected (hides empty state automatically via fetchMessages)
                enableChatInput();
                // Bind store link click
                bindStoreLinkClick();
                // NEW: Bind add members button for groups
                $('.add-members-btn').off('click').on('click', function(e) {
                    e.stopPropagation();
                    var action = 'add-members';
                    // Copy the code from dropdown handler for add-members
                    var q = '';
                    $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_search_add_to_group', nonce: wcfmVChat.nonce, group_id: id, q: q }, function(resp) {
                        if (!resp || resp.error) return;
                        var win = $('<div class="new-chat-popup" style="position:fixed;right:20px;bottom:80px;width:300px;background:#fff;box-shadow:0 20px 60px rgba(0,0,0,0.1);border-radius:20px;padding:16px;z-index:100000;max-height:400px;overflow-y:auto;">');
                        var html = '<div><h3>Add Members</h3><div class="search-input"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg><input type="search" id="add-group-search" placeholder="Search vendors..." /></div><div id="add-group-results" style="max-height:200px;overflow-y:auto;"></div><button id="add-selected-members" style="width:100%; padding:10px; background:#007AFF; color:#fff; border:none; border-radius:4px; margin-top:8px; cursor:pointer; font-weight:500; display:none;">Add Selected</button></div>';
                        win.html(html);
                        $('body').append(win);
                        // Render initial results
                        renderAddResults(resp.data, win);
                        // Search input
                        var searchTimeout;
                        win.find('#add-group-search').on('input', function() {
                            clearTimeout(searchTimeout);
                            searchTimeout = setTimeout(function() {
                                var qq = win.find('#add-group-search').val();
                                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_search_add_to_group', nonce: wcfmVChat.nonce, group_id: id, q: qq }, function(rresp) {
                                    renderAddResults(rresp.data, win);
                                }, 'json');
                            }, 300);
                        });
                        // Selected members
                        var selected = [];
                        function renderAddResults(data, w) {
                            var resDiv = w.find('#add-group-results').empty();
                            if (!data || data.length === 0) {
                                resDiv.html('<div style="padding:20px; text-align:center; color:#8E8E93;">No vendors found.</div>');
                                return;
                            }
                            data.forEach(function(v) {
                                var avatar = v.avatar ? '<img src="' + v.avatar + '" style="width:40px;height:40px;border-radius:50%;margin-right:12px;">' : '<div style="width:40px;height:40px;border-radius:50%;background:#E5E5EA;margin-right:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + v.display_name.charAt(0) + '</div>';
                                var div = $('<div style="display:flex;align-items:center;padding:12px;border-bottom:1px solid #E5E5EA;cursor:pointer;" data-id="' + v.ID + '">').html(avatar + '<div><strong>' + v.display_name + '</strong><div style="font-size:12px;color:#8E8E93;">' + v.store_name + '</div></div><input type="checkbox" style="margin-left:auto;">');
                                div.find('input[type="checkbox"]').on('change', function() {
                                    var vid = div.data('id');
                                    if (this.checked) {
                                        if (!selected.includes(vid)) selected.push(vid);
                                    } else {
                                        selected = selected.filter(function(s) { return s != vid; });
                                    }
                                    w.find('#add-selected-members').toggle(selected.length > 0);
                                });
                                resDiv.append(div);
                            });
                        }
                        win.find('#add-selected-members').on('click', function(e) {
                            e.stopPropagation();
                            if (selected.length === 0) return;
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_add_members_to_group', nonce: wcfmVChat.nonce, group_id: id, members: selected }, function(resp) {
                                if (resp && resp.success) {
                                    alert('Added ' + resp.data.added + ' members.');
                                    refreshConversationsList();
                                } else {
                                    alert('Error adding members.');
                                }
                                win.remove();
                            }, 'json');
                        });
                        if (isMobile) {
                            win.addClass('new-chat-popup-mobile');
                        }
                        $(document).one('click.addmembers', function(e) { if (!$(e.target).closest(win).length) win.remove(); });
                    }, 'json');
                });
                function bindStoreLinkClick() {
                    $('.store-link').off('click').on('click', function(e) {
                        e.stopPropagation();
                        var storeUrl = $(this).data('store-url') || currentStoreUrl;
                        if (storeUrl) {
                            window.open(storeUrl, '_blank');
                        }
                    });
                }
                // Mobile: Open full chat
                if (isMobile) {
                    $('#wcfm-vchat-modal').addClass('chat-open');
                    $('#wcfm-vchat-back').show();
                    $('#wcfm-vchat-left').hide();
                }
                // Unbind previous to avoid multiples
                $(document).off('click.newchat');
                // FIXED: Menu dropdown - Now positions above menu button; ensure visibility - FIXED for desktop: use fixed positioning, right-aligned
                $('.menu-btn').off('click').on('click', function(e) {
                    e.stopPropagation();
                    var dropdown = $('#wcfm-vchat-dropdown');
                    var headerPos = $('#wcfm-vchat-chat-header').offset();
                    var headerHeight = $('#wcfm-vchat-chat-header').outerHeight();
                    dropdown.css({ display: 'block' }); // Show first to measure height
                    var dropdownHeight = dropdown.outerHeight();
                    // FIXED: Use fixed positioning for both, right-aligned above header
                    dropdown.css({
                        position: 'fixed',
                        top: headerPos.top - dropdownHeight - 10, // Above header
                        right: isMobile ? '10px' : '20px', // Align to modal right
                        left: 'auto',
                        display: 'block'
                    });
                    dropdown.find('li[data-action="block-vendor"]').toggle(type === 'individual');
                    dropdown.find('li[data-action="exit-group"]').toggle(type === 'group' && id != wcfmVChat.all_group_id);
                    dropdown.find('li[data-action="view-members"]').toggle(type === 'group');
                    dropdown.find('li[data-action="view-profile"]').toggle(type === 'individual' && currentStoreUrl); // NEW
                    dropdown.find('li[data-action="add-to-all-vendors"]').toggle(type === 'individual');
                    dropdown.find('li[data-action="add-members"]').toggle(type === 'group');
                    dropdown.find('li[data-action="delete-group"]').toggle(type === 'group' && id != wcfmVChat.all_group_id);
                });
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.menu-btn, .wcfm-vchat-dropdown').length) {
                        $('#wcfm-vchat-dropdown').hide();
                    }
                });
                // FIXED: Bind dropdown actions - Removed premature hide(); let document click close after action
                $('#wcfm-vchat-dropdown').off('click', 'li').on('click', 'li', function(e) {
                    e.stopPropagation(); // Prevent document close during click
                    var action = $(this).data('action');
                    // Do NOT hide here - let document click handle after AJAX
                    if (action === 'clear-chat') {
                        if (confirm('Clear this chat? Messages will be deleted for everyone.')) {
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_clear_chat', nonce: wcfmVChat.nonce, type: type, id: id }, function(resp) {
                                if (resp && resp.success) {
                                    $('#wcfm-vchat-messages').empty();
                                    currentLastId = 0;
                                    refreshConversationsList();
                                }
                            }, 'json').always(function() {
                                $('#wcfm-vchat-dropdown').hide(); // Hide after action completes
                            });
                        }
                    } else if (action === 'view-profile') { // FIXED: Ensure store URL is used
                        if (currentStoreUrl) {
                            window.open(currentStoreUrl, '_blank');
                            $('#wcfm-vchat-dropdown').hide();
                        } else {
                            alert('Store profile not available.');
                            $('#wcfm-vchat-dropdown').hide();
                        }
                    } else if (action === 'add-to-all-vendors') {
                        if (confirm('Add this vendor to All Vendors group?')) {
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_add_to_all_group', nonce: wcfmVChat.nonce, user_id: id }, function(resp) {
                                if (resp && resp.success) {
                                    alert( resp.data.added ? 'Added to All Vendors.' : 'Already in All Vendors.' );
                                } else {
                                    alert('Error adding.');
                                }
                            }, 'json').always(function() {
                                $('#wcfm-vchat-dropdown').hide();
                            });
                        }
                    } else if (action === 'block-vendor') {
                        if (confirm('Block this vendor? You will no longer receive messages from them.')) {
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_block_vendor', nonce: wcfmVChat.nonce, blocked_id: id }, function(resp) {
                                if (resp && resp.success) {
                                    currentConversation = null;
                                    currentStoreUrl = '';
                                    $('#wcfm-vchat-chat-header .name').html('Select a conversation');
                                    $('#wcfm-vchat-messages').empty();
                                    $('.wcfm-vchat-right').hide();
                                    if (isMobile) {
                                        $('#wcfm-vchat-modal').removeClass('chat-open');
                                        $('#wcfm-vchat-back').hide();
                                        $('#wcfm-vchat-left').show();
                                    }
                                    refreshConversationsList();
                                }
                            }, 'json').always(function() {
                                $('#wcfm-vchat-dropdown').hide(); // Hide after action completes
                            });
                        }
                    } else if (action === 'view-members') {
                        viewGroupMembers(id);
                        $('#wcfm-vchat-dropdown').hide(); // Immediate hide for view
                    } else if (action === 'add-members') {
                        // Open add members popup (same as button)
                        var q = '';
                        $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_search_add_to_group', nonce: wcfmVChat.nonce, group_id: id, q: q }, function(resp) {
                            if (!resp || resp.error) return;
                            var win = $('<div class="new-chat-popup" style="position:fixed;right:20px;bottom:80px;width:300px;background:#fff;box-shadow:0 20px 60px rgba(0,0,0,0.1);border-radius:20px;padding:16px;z-index:100000;max-height:400px;overflow-y:auto;">');
                            var html = '<div><h3>Add Members</h3><div class="search-input"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg><input type="search" id="add-group-search" placeholder="Search vendors..." /></div><div id="add-group-results" style="max-height:200px;overflow-y:auto;"></div><button id="add-selected-members" style="width:100%; padding:10px; background:#007AFF; color:#fff; border:none; border-radius:4px; margin-top:8px; cursor:pointer; font-weight:500; display:none;">Add Selected</button></div>';
                            win.html(html);
                            $('body').append(win);
                            // Render initial results
                            renderAddResults(resp.data, win);
                            // Search input
                            var searchTimeout;
                            win.find('#add-group-search').on('input', function() {
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(function() {
                                    var qq = win.find('#add-group-search').val();
                                    $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_search_add_to_group', nonce: wcfmVChat.nonce, group_id: id, q: qq }, function(rresp) {
                                        renderAddResults(rresp.data, win);
                                    }, 'json');
                                }, 300);
                            });
                            // Selected members
                            var selected = [];
                            function renderAddResults(data, w) {
                                var resDiv = w.find('#add-group-results').empty();
                                if (!data || data.length === 0) {
                                    resDiv.html('<div style="padding:20px; text-align:center; color:#8E8E93;">No vendors found.</div>');
                                    return;
                                }
                                data.forEach(function(v) {
                                    var avatar = v.avatar ? '<img src="' + v.avatar + '" style="width:40px;height:40px;border-radius:50%;margin-right:12px;">' : '<div style="width:40px;height:40px;border-radius:50%;background:#E5E5EA;margin-right:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + v.display_name.charAt(0) + '</div>';
                                    var div = $('<div style="display:flex;align-items:center;padding:12px;border-bottom:1px solid #E5E5EA;cursor:pointer;" data-id="' + v.ID + '">').html(avatar + '<div><strong>' + v.display_name + '</strong><div style="font-size:12px;color:#8E8E93;">' + v.store_name + '</div></div><input type="checkbox" style="margin-left:auto;">');
                                    div.find('input[type="checkbox"]').on('change', function() {
                                        var vid = div.data('id');
                                        if (this.checked) {
                                            if (!selected.includes(vid)) selected.push(vid);
                                        } else {
                                            selected = selected.filter(function(s) { return s != vid; });
                                        }
                                        w.find('#add-selected-members').toggle(selected.length > 0);
                                    });
                                    resDiv.append(div);
                                });
                            }
                            win.find('#add-selected-members').on('click', function(e) {
                                e.stopPropagation();
                                if (selected.length === 0) return;
                                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_add_members_to_group', nonce: wcfmVChat.nonce, group_id: id, members: selected }, function(resp) {
                                    if (resp && resp.success) {
                                        alert('Added ' + resp.data.added + ' members.');
                                        refreshConversationsList();
                                    } else {
                                        alert('Error adding members.');
                                    }
                                    win.remove();
                                }, 'json');
                            });
                            if (isMobile) {
                                win.addClass('new-chat-popup-mobile');
                            }
                            $(document).one('click.addmembers', function(e) { if (!$(e.target).closest(win).length) win.remove(); });
                        }, 'json');
                        $('#wcfm-vchat-dropdown').hide();
                    } else if (action === 'exit-group') {
                        if (confirm('Exit this group? You will no longer see messages here.')) {
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_exit_group', nonce: wcfmVChat.nonce, group_id: id }, function(resp) {
                                if (resp && resp.success) {
                                    currentConversation = null;
                                    currentStoreUrl = '';
                                    $('#wcfm-vchat-chat-header .name').html('Select a conversation');
                                    $('#wcfm-vchat-messages').empty();
                                    $('.wcfm-vchat-right').hide();
                                    if (isMobile) {
                                        $('#wcfm-vchat-modal').removeClass('chat-open');
                                        $('#wcfm-vchat-back').hide();
                                        $('#wcfm-vchat-left').show();
                                    }
                                    refreshConversationsList();
                                }
                            }, 'json').always(function() {
                                $('#wcfm-vchat-dropdown').hide(); // Hide after action completes
                            });
                        }
                    } else if (action === 'delete-group') {
                        if (confirm('Delete this group? All messages and members will be removed.')) {
                            $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_delete_group', nonce: wcfmVChat.nonce, group_id: id }, function(resp) {
                                if (resp && resp.success) {
                                    currentConversation = null;
                                    currentStoreUrl = '';
                                    $('#wcfm-vchat-chat-header .name').html('Select a conversation');
                                    $('#wcfm-vchat-messages').empty();
                                    $('.wcfm-vchat-right').hide();
                                    if (isMobile) {
                                        $('#wcfm-vchat-modal').removeClass('chat-open');
                                        $('#wcfm-vchat-back').hide();
                                        $('#wcfm-vchat-left').show();
                                    }
                                    refreshConversationsList();
                                } else {
                                    alert('Error deleting group: ' + (resp ? resp.data : 'Unknown'));
                                }
                            }, 'json').always(function() {
                                $('#wcfm-vchat-dropdown').hide();
                            });
                        }
                    }
                });
                // ENHANCED: First mark as read, then fetch messages and refresh list to hide dot
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_mark_read', nonce: wcfmVChat.nonce, type: type, id: id }).done(function(resp) {
                    if (resp && !resp.error) {
                        fetchMessages(type, id, 0, false); // Initial full fetch
                        refreshConversationsList(); // Hide dot immediately
                    }
                });
                startPolling(); // OPTIMIZED: Always restart polling on chat select
            }
            function viewGroupMembers(group_id) {
                $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_get_group_members', nonce: wcfmVChat.nonce, group_id: group_id }, function(resp) {
                    if (!resp || resp.error) return;
                    var win = $('<div class="new-chat-popup" style="position:fixed;right:20px;bottom:80px;width:300px;background:#fff;box-shadow:0 20px 60px rgba(0,0,0,0.1);border-radius:20px;padding:16px;z-index:100000;max-height:400px;overflow-y:auto;">');
                    var html = '<div><h3>Group Members</h3>';
                    resp.data.forEach(function(m) {
                        var avatar = m.avatar ? '<img src="' + m.avatar + '" style="width:40px;height:40px;border-radius:50%;margin-right:12px;">' : '<div style="width:40px;height:40px;border-radius:50%;background:#E5E5EA;margin-right:12px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#8E8E93;">' + m.display_name.charAt(0) + '</div>';
                        var storeHtml = m.store_name ? '<div class="store-link" data-store-url="' + (m.store_url || '') + '">' + m.store_name + '</div>' : '<div style="font-size:12px;color:#8E8E93;">No store</div>';
                        html += '<div style="display:flex;align-items:center;padding:12px;border-bottom:1px solid #E5E5EA;">' + avatar + '<div><strong>' + m.display_name + '</strong>' + storeHtml + '</div></div>';
                    });
                    html += '</div>';
                    win.html(html);
                    $('body').append(win);
                    // NEW: Bind store link clicks in group members popup
                    win.find('.store-link').on('click', function(e) {
                        e.stopPropagation();
                        var url = $(this).data('store-url');
                        if (url) window.open(url, '_blank');
                    });
                    // ENHANCED: On mobile, apply centered full-screen style
                    if (isMobile) {
                        win.addClass('new-chat-popup-mobile');
                    }
                    $(document).one('click.newchat', function(e) { if (!$(e.target).closest(win).length) win.remove(); });
                }, 'json');
            }
            // OPTIMIZED: Enhanced fetchMessages with ASC order (oldest first), append build, scroll to bottom; supports incremental (isPoll, lastId); fixed date logic and no doubling; detects new from other to trigger list refresh
            function fetchMessages(type, id, lastId = 0, isPoll = false) {
                if (isRefreshing) return;
                isRefreshing = true;
                var postData = { action: 'wcfm_vchat_fetch_messages', nonce: wcfmVChat.nonce, type: type, id: id };
                if (lastId > 0) postData.last_id = lastId;
                var box = $('#wcfm-vchat-messages');
                $.post(wcfmVChat.ajax_url, postData)
                .done(function(resp) {
                    if (!resp || resp.error) {
                        console.error('Fetch messages error:', resp); // Debug log
                        if (!isPoll) box.html('<div class="no-messages">Connection error - retrying soon...</div>');
                        isRefreshing = false;
                        return;
                    }
                    if (!resp.data || resp.data.length === 0) {
                        if (!isPoll) box.html('<div class="no-messages">No messages yet. Start the conversation!</div>');
                        isRefreshing = false;
                        return;
                    }
                    var messagesHtml = '';
                    var prevDate = null;
                    var today = new Date().toDateString();
                    var yesterday = new Date(Date.now() - 86400000).toDateString();
                    var hasNewFromOther = false; // OPTIMIZED: Detect if any new message from other user
                    // FIXED: Server returns ASC (oldest first), so loop and append messages, add date before new group
                    resp.data.forEach(function(m) {
                        if (isPoll && m.sender_id != wcfmVChat.current_user_id) hasNewFromOther = true; // Flag for list refresh
                        var msgDate = new Date(m.created_at).toDateString();
                        if (msgDate !== prevDate) {
                            var dateLabel;
                            if (msgDate === today) {
                                dateLabel = 'Today';
                            } else if (msgDate === yesterday) {
                                dateLabel = 'Yesterday';
                            } else {
                                dateLabel = new Date(m.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            }
                            messagesHtml += '<div class="date-separator">' + dateLabel + '</div>';
                            prevDate = msgDate;
                        }
                        var senderName = m.sender_id == wcfmVChat.current_user_id ? 'You' : (m.sender_name || 'Vendor');
                        var senderAvatar = m.sender_avatar ? '<img src="' + m.sender_avatar + '" class="avatar" alt="" onerror="this.src=\'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIxNiIgZmlsbD0iI0U1RTVFQSIvPjx0ZXh0IHg9IjE2IiB5PSIxOSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzhFOEU5MyI+Uw0KPC90ZXh0Pjwvc3ZnPg==\';">' : '<div class="avatar" style="background:#E5E5EA;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#8E8E93;">' + senderName.charAt(0) + '</div>';
                        var isMe = m.sender_id == wcfmVChat.current_user_id;
                        var timeStr = new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                        // UPDATED: Use attachment parsing
                        var parsed = parseAttachment(m.content);
                        var bubbleContent = parsed.html;
                        var messageHtml = '<div class="message ' + (isMe ? 'me' : 'them') + '"><div class="avatar">' + senderAvatar + '</div><div><div class="sender">' + senderName + '</div><div class="bubble">' + bubbleContent + '</div><div class="time">' + timeStr + '</div></div></div>';
                        messagesHtml += messageHtml;
                    });
                    if (isPoll) {
                        // NEW: For poll, append new messages only
                        if (messagesHtml) {
                            box.append(messagesHtml);
                            box.scrollTop(box[0].scrollHeight); // Scroll to bottom
                        }
                        // OPTIMIZED: Refresh list only if new messages from other user received
                        if (hasNewFromOther) {
                            refreshConversationsList();
                        }
                    } else {
                        // Initial full load
                        box.html(messagesHtml);
                        box.scrollTop(box[0].scrollHeight); // Scroll to bottom
                    }
                    // UPDATE last ID to newest
                    currentLastId = resp.data[resp.data.length - 1].id || currentLastId;
                    isRefreshing = false;
                })
                .fail(function(jqXHR, textStatus, error) {
                    console.error('AJAX fail fetchMessages:', textStatus, error); // ADDED: Debug
                    if (!isPoll) $('#wcfm-vchat-messages').html('<div class="no-messages">Failed to load messages. Check connection.</div>');
                    isRefreshing = false;
                });
            }
            $('#wcfm-vchat-send').on('click', sendMessage);
            $('#wcfm-vchat-input').on('keypress', function(e) { if (e.which == 13 && !e.shiftKey) { e.preventDefault(); sendMessage(); } });
            function sendMessage() {
                var text = $('#wcfm-vchat-input').val().trim();
                if (!text && !window.currentAttachment) return;
                if (text) {
                    sendContent(text);
                    $('#wcfm-vchat-input').val('');
                }
                if (window.currentAttachment) {
                    var att = window.currentAttachment;
                    var timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                    var previewUrl = att.previewUrl || null;
                    var displayHtml;
                    var fileNameWithoutExt = att.name.replace(/\.[^/.]+$/, "");
                    if (att.is_image) {
                        displayHtml = '<img src="' + previewUrl + '" style="max-width:200px; border-radius:8px; margin:4px 0;" /> <small class="upload-progress">(0%)</small>';
                    } else {
                        displayHtml = '📎 ' + att.name + ' <small class="upload-progress">(0%)</small>';
                    }
                    var messageHtml = '<div class="message me optimistic uploading"><div class="avatar" style="background:#E5E5EA;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;color:#8E8E93;">Y</div><div><div class="bubble">' + displayHtml + '</div><div class="time">' + timeStr + '</div></div></div>';
                    var box = $('#wcfm-vchat-messages');
                    box.append(messageHtml);
                    box.scrollTop(box[0].scrollHeight);
                    if (att.isTemp) {
                        // Upload
                        var formData = new FormData();
                        formData.append('action', 'wcfm_vchat_upload_file');
                        formData.append('nonce', wcfmVChat.nonce);
                        formData.append('file', att.file);
                        var xhr = $.ajax({
                            url: wcfmVChat.ajax_url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            xhr: function() {
                                var x = new window.XMLHttpRequest();
                                x.upload.addEventListener('progress', function(evt) {
                                    if (evt.lengthComputable) {
                                        var percent = Math.round((evt.loaded / evt.total) * 100);
                                        box.find('.upload-progress').text('(' + percent + '%)');
                                    }
                                }, false);
                                return x;
                            },
                            success: function(resp) {
                                box.find('.uploading').removeClass('uploading optimistic');
                                if (resp && resp.success) {
                                    var realUrl = resp.data.url;
                                    var realName = resp.data.name;
                                    var realIsImage = resp.data.is_image;
                                    var realDisplayHtml;
                                    var realFileNameWithoutExt = realName.replace(/\.[^/.]+$/, "");
                                    if (realIsImage) {
                                        realDisplayHtml = '<img src="' + realUrl + '" alt="' + realName + '" style="max-width:200px; border-radius:8px; margin:4px 0;" loading="lazy" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"><small style="color:#8E8E93; display:none;">🖼️ Image: ' + realFileNameWithoutExt + '</small>';
                                    } else {
                                        realDisplayHtml = '📎 <a href="' + realUrl + '" target="_blank" download="' + realName + '">' + realFileNameWithoutExt + '</a> <small style="color:#8E8E93;">(download)</small>';
                                    }
                                    box.find('.uploading .bubble').html(realDisplayHtml);
                                    var realContent = 'File: ' + realName + ' (' + (realIsImage ? 'image' : 'file') + ') ' + realUrl;
                                    $.post(wcfmVChat.ajax_url, { action: 'wcfm_vchat_send_message', nonce: wcfmVChat.nonce, type: currentConversation.type, id: currentConversation.id, content: realContent }, function() {
                                        refreshConversationsList(); // OPTIMIZED: Refresh after successful file send
                                        // NEW: Trigger WS for image
                                        if (ws && ws.readyState === WebSocket.OPEN) {
                                            ws.send(JSON.stringify({type: 'image_sent', convo_type: currentConversation.type, convo_id: currentConversation.id, content: realContent}));
                                        }
                                    }, 'json');
                                } else {
                                    box.find('.uploading').remove();
                                    alert('Upload failed: ' + (resp ? resp.data : 'Unknown error'));
                                }
                                clearAttachment();
                            },
                            error: function() {
                                box.find('.uploading').remove();
                                alert('Upload error. Please try again.');
                                clearAttachment();
                            }
                        });
                    } else {
                        // Already uploaded
                        box.find('.optimistic').removeClass('optimistic uploading');
                        var content = 'File: ' + att.name + ' (' + (att.is_image ? 'image' : 'file') + ') ' + att.url;
                        sendContent(content, false); // No optimistic, already shown
                        clearAttachment();
                    }
                }
            }
            // Enhanced Emoji Picker for Mobile (Paginated)
            $('.input-emoji').on('click', function(e) {
                e.stopPropagation();
                if (emojiPicker) {
                    emojiPicker.remove();
                    emojiPicker = null;
                    currentEmojiPage = 0;
                    return;
                }
                var picker = $('<div id="emoji-picker">');
                renderEmojiPage(picker, 0);
                // Add pagination buttons on mobile
                if (isMobile && emojiPages.length > 1) {
                    var prevBtn = $('<button style="grid-column: 1 / -1; padding: 4px;">← Prev</button>').on('click', function(e) {
                        e.stopPropagation();
                        if (currentEmojiPage > 0) renderEmojiPage(picker, --currentEmojiPage);
                    });
                    var nextBtn = $('<button style="grid-column: 1 / -1; padding: 4px;">Next →</button>').on('click', function(e) {
                        e.stopPropagation();
                        if (currentEmojiPage < emojiPages.length - 1) renderEmojiPage(picker, ++currentEmojiPage);
                    });
                    picker.append(prevBtn).append(nextBtn);
                }
                $(this).after(picker);
                emojiPicker = picker;
            });
            function renderEmojiPage(picker, page) {
                picker.empty();
                emojiPages[page].forEach(function(em) {
                    var btn = $('<button>').html(em).on('click', function(e) {
                        e.stopPropagation();
                        var input = $('#wcfm-vchat-input');
                        var start = input[0].selectionStart;
                        var end = input[0].selectionEnd;
                        var text = input.val();
                        input.val(text.substring(0, start) + em + text.substring(end));
                        input[0].selectionStart = input[0].selectionEnd = start + em.length;
                        input.focus();
                        picker.remove();
                        emojiPicker = null;
                        currentEmojiPage = 0;
                    });
                    picker.append(btn);
                });
            }
            $(document).on('click', function(e) {
                if (emojiPicker && !$(e.target).closest('.input-emoji, #emoji-picker').length) {
                    emojiPicker.remove();
                    emojiPicker = null;
                }
                if (attachPicker && !$(e.target).closest('.input-attach, .attach-options').length) {
                    attachPicker.remove();
                    attachPicker = null;
                }
            });
            // UPDATED: File attachment preview before send
            $('.input-attach').on('click', function(e) {
                e.stopPropagation();
                if (attachPicker) {
                    attachPicker.remove();
                    attachPicker = null;
                    return;
                }
                var options = $('<div class="attach-options">');
                options.append('<button id="camera-btn">📷 Camera</button>');
                options.append('<button id="gallery-btn">🖼️ Gallery</button>');
                options.append('<button id="file-btn">📄 Document</button>');
                $(this).after(options);
                attachPicker = options;
                options.on('click', '#camera-btn', function(e) {
                    e.stopPropagation();
                    createFileInput('image/*', 'environment');
                });
                options.on('click', '#gallery-btn', function(e) {
                    e.stopPropagation();
                    createFileInput('image/*', '');
                });
                options.on('click', '#file-btn', function(e) {
                    e.stopPropagation();
                    createFileInput('*/*', ''); // Allow all for documents
                });
            });
            function createFileInput(accept, capture) {
                isFileSelecting = true; // Set flag to prevent close during selection
                var input = $('<input type="file" accept="' + accept + '"' + (capture ? ' capture="' + capture + '"' : '') + '>');
                input.on('change', function(e) {
                    e.stopPropagation();
                    handleFileSelect(this.files);
                    isFileSelecting = false; // Reset flag after handling
                    if (attachPicker) {
                        attachPicker.remove();
                        attachPicker = null;
                    }
                    $(this).remove();
                });
                input.css({ position: 'absolute', left: '-9999px' }).appendTo('body');
                input[0].click();
            }
            // UPDATED: Handle file select with preview - FIXED: Preview before send, upload on send
            function handleFileSelect(files) {
                if (!files || files.length === 0 || !currentConversation) return;
                var file = files[0];
                var is_image = file.type.startsWith('image/');
                // Temp preview
                var previewDiv = $('<div class="attachment-preview"></div>');
                var removeSpan = $('<span class="remove-attachment">×</span>');
                if (is_image) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        window.currentAttachment = {
                            name: file.name,
                            is_image: true,
                            file: file,
                            previewUrl: e.target.result,
                            isTemp: true
                        };
                        var img = $('<img>').attr('src', e.target.result).css({
                            maxWidth: '60px',
                            maxHeight: '60px',
                            borderRadius: '8px',
                            objectFit: 'cover'
                        });
                        var nameSpan = $('<span>').text(file.name).css({fontSize: '12px', wordBreak: 'break-word'});
                        previewDiv.append(img, nameSpan, removeSpan).css({
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px'
                        });
                        bindRemoveAttachment();
                    };
                    reader.readAsDataURL(file);
                } else {
                    window.currentAttachment = {
                        name: file.name,
                        is_image: false,
                        file: file,
                        isTemp: true
                    };
                    var iconSpan = $('<span>📎 </span>');
                    var nameSpan = $('<span>').text(file.name).css({fontSize: '12px', wordBreak: 'break-word'});
                    previewDiv.append(iconSpan, nameSpan, removeSpan).css({
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px'
                    });
                    bindRemoveAttachment();
                }
                $('#attachments-container').html(previewDiv);
            }
            // UPDATED: Drag and Drop Support with preview - FIXED: Preview dropped files
            $('#wcfm-vchat-messages').on('dragover', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'copy';
                $(this).addClass('drag-over');
            }).on('dragleave', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
            }).on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0 && currentConversation) {
                    handleFileSelect(files);
                }
            });
            $('.wcfm-vchat-tabs button').on('click', function(e) {
                e.stopPropagation(); // Prevent outside click trigger
                var tab = $(this).data('tab');
                // On mobile, if chat is open, close it first
                if (isMobile && $('#wcfm-vchat-modal').hasClass('chat-open')) {
                    $('#wcfm-vchat-back').click();
                }
                loadConversations(tab); // Immediate load
            });
        });
    })(jQuery);
    </script>
    <?php
}
// NEW: AJAX for toggling auto-update persistence on server
add_action( 'wp_ajax_wcfm_vchat_toggle_auto_update', 'wcfm_vchat_toggle_auto_update' );
function wcfm_vchat_toggle_auto_update() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $active = isset( $_POST['active'] ) ? (bool) $_POST['active'] : false;
    update_user_meta( get_current_user_id(), 'wcfm_vchat_auto_update', $active );
    wp_send_json_success();
}
// NEW: AJAX for getting store URL (for dynamic fetch in selectConversation)
add_action( 'wp_ajax_wcfm_vchat_get_store_url_ajax', 'wcfm_vchat_get_store_url_ajax' );
function wcfm_vchat_get_store_url_ajax() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    if ( ! $user_id ) wp_send_json_error( 'invalid_user' );
    $store_url = wcfm_vchat_get_store_url( $user_id );
    if ( $store_url ) {
        wp_send_json_success( $store_url );
    } else {
        wp_send_json_error( 'no_store' );
    }
}
/**
 * NEW: AJAX handler for file uploads to media library - FIXED: Ensure absolute URLs returned
 */
add_action( 'wp_ajax_wcfm_vchat_upload_file', 'wcfm_vchat_upload_file' );
function wcfm_vchat_upload_file() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    if ( empty( $_FILES['file']['name'] ) ) wp_send_json_error('no_file');
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    $uploaded = media_handle_upload( 'file', 0 ); // Attach to post 0 (uncategorized)
    if ( is_wp_error( $uploaded ) ) {
        wp_send_json_error( $uploaded->get_error_message() );
    }
    $url = wp_get_attachment_url( $uploaded ); // This is already absolute
    $filename = basename( get_attached_file( $uploaded ) );
    $filetype = wp_check_filetype( $filename );
    $is_image = strpos( $filetype['type'], 'image/' ) !== false;
    // Set attachment visibility to private if needed, but for chat, public is fine
    wp_update_post( array( 'ID' => $uploaded, 'post_status' => 'inherit' ) );
    wp_send_json_success( array( 'url' => $url, 'name' => $filename, 'is_image' => $is_image ) );
}
/**
 * NEW: Mark conversation as read
 */
add_action( 'wp_ajax_wcfm_vchat_mark_read', 'wcfm_vchat_mark_read' );
function wcfm_vchat_mark_read() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $me = get_current_user_id();
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$type || !$id) wp_send_json_error('invalid');
    $now = current_time('mysql');
    $key = 'wcfm_vchat_last_read_' . $me . '_' . $type . '_' . $id;
    set_transient( $key, $now, HOUR_IN_SECONDS );
    wp_send_json_success();
}
/**
 * NEW: Add to All Vendors group
 */
add_action( 'wp_ajax_wcfm_vchat_add_to_all_group', 'wcfm_vchat_add_to_all_group' );
function wcfm_vchat_add_to_all_group() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
    if ( ! $user_id || $user_id == get_current_user_id() ) wp_send_json_error( 'invalid' );
    if ( ! wcfm_vchat_is_vendor( $user_id ) ) wp_send_json_error( 'not_vendor' );
    global $wpdb;
    $all_group_id = get_option( 'wcfm_vchat_all_vendors_group_id' );
    if ( ! $all_group_id ) wp_send_json_error( 'no_group' );
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants WHERE group_id = %d AND user_id = %d", $all_group_id, $user_id ) );
    if ( $exists ) wp_send_json_success( array( 'added' => false ) ); // Already added
    $wpdb->insert( $participants, array( 'group_id' => $all_group_id, 'user_id' => $user_id ) );
    wp_send_json_success( array( 'added' => true ) );
}
/**
 * NEW: Delete group
 */
add_action( 'wp_ajax_wcfm_vchat_delete_group', 'wcfm_vchat_delete_group' );
function wcfm_vchat_delete_group() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
    if ( ! $group_id ) wp_send_json_error( 'invalid' );
    $all_group_id = get_option( 'wcfm_vchat_all_vendors_group_id' );
    if ( $group_id == $all_group_id ) wp_send_json_error( 'cannot_delete_all' );
    $me = get_current_user_id();
    $groups_table = $wpdb->prefix . 'wcfm_vchat_groups';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    // Check if creator
    $creator = $wpdb->get_var( $wpdb->prepare( "SELECT created_by FROM $groups_table WHERE id = %d", $group_id ) );
    if ( $creator != $me ) wp_send_json_error( 'not_creator' );
    // Delete all
    $wpdb->delete( $participants, array( 'group_id' => $group_id ) );
    $wpdb->delete( $messages_table, array( 'group_id' => $group_id ) );
    $wpdb->delete( $groups_table, array( 'id' => $group_id ) );
    // Invalidate caches
    delete_transient('wcfm_vchat_convs_' . $me . '_groups');
    wp_send_json_success();
}
/**
 * NEW: Search vendors to add to group
 */
add_action( 'wp_ajax_wcfm_vchat_search_add_to_group', 'wcfm_vchat_search_add_to_group' );
function wcfm_vchat_search_add_to_group() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
    $group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
    if ( ! $group_id ) wp_send_json_error( 'no_group' );
    global $wpdb;
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $in_group = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM $participants WHERE group_id = %d", $group_id ) );
    $args = array( 'role__in' => array('wcfm_vendor','seller','vendor'), 'number' => 20, 'exclude' => array_merge( $in_group, array( get_current_user_id() ) ) );
    if ( $q ) $args['search'] = '*'.esc_attr( $q ).'*';
    $users = get_users( $args );
    $data = array();
    foreach( $users as $u ){
        $store_name = '';
        $store_url = wcfm_vchat_get_store_url( $u->ID );
        if ( function_exists( 'wcfmmp_get_store' ) ) {
            $store = wcfmmp_get_store( $u->ID );
            if ( $store && isset( $store->store_name ) ) $store_name = $store->store_name;
        }
        $data[] = array( 'ID'=>$u->ID, 'display_name'=>$u->display_name, 'store_name'=>$store_name, 'store_url' => $store_url, 'avatar'=>get_avatar_url( $u->ID, array( 'size' => 40 ) ) );
    }
    wp_send_json_success( $data );
}
/**
 * NEW: Add members to existing group
 */
add_action( 'wp_ajax_wcfm_vchat_add_members_to_group', 'wcfm_vchat_add_members_to_group' );
function wcfm_vchat_add_members_to_group() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
    $members = isset( $_POST['members'] ) ? array_map( 'intval', (array) $_POST['members'] ) : array();
    if ( ! $group_id || empty( $members ) ) wp_send_json_error( 'invalid' );
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    // Check if in group
    $in_group = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants WHERE group_id=%d AND user_id=%d", $group_id, $me ) );
    if ( ! $in_group ) wp_send_json_error( 'not_in_group' );
    $added = 0;
    foreach ( $members as $uid ) {
        if ( $uid != $me && wcfm_vchat_is_vendor( $uid ) ) {
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants WHERE group_id=%d AND user_id=%d", $group_id, $uid ) );
            if ( ! $exists ) {
                $wpdb->insert( $participants, array( 'group_id' => $group_id, 'user_id' => $uid ) );
                $added++;
                // Invalidate cache for new member
                delete_transient('wcfm_vchat_convs_' . $uid . '_groups');
            }
        }
    }
    // Invalidate own cache
    delete_transient('wcfm_vchat_convs_' . $me . '_groups');
    wp_send_json_success( array( 'added' => $added ) );
}
/**
 * AJAX handlers (added error logging for debugging) - FIXED: fetch_messages now uses ASC ORDER for oldest first; supports last_id for incremental
 */
add_action( 'wp_ajax_wcfm_vchat_clear_chat', 'wcfm_vchat_clear_chat' );
function wcfm_vchat_clear_chat() {
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $type = isset($_POST['type'])? sanitize_text_field($_POST['type']) : 'individual';
    $id = isset($_POST['id'])? intval($_POST['id']) : 0;
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    if ( $type == 'individual' ){
        $other = $id;
        if ( ! $other ) wp_send_json_error('invalid');
        $blocked = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_vchat_blocks WHERE (blocker_id=%d AND blocked_id=%d) OR (blocker_id=%d AND blocked_id=%d)", $me, $other, $other, $me ) );
        if ( $blocked ) wp_send_json_error('blocked');
        $wpdb->delete( $messages_table, array( '(sender_id = %d AND receiver_id = %d) OR (sender_id = %d AND receiver_id = %d)', 'is_group' => 0 ), array( '%d', '%d', '%d', '%d', '%d' ) );
    } else {
        $group_id = $id;
        $p = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $participants WHERE group_id=%d AND user_id=%d", $group_id, $me ) );
        if ( ! $p ) wp_send_json_error('not_in_group');
        $wpdb->delete( $messages_table, array( 'group_id' => $group_id ) );
    }
    // Invalidate caches
    delete_transient('wcfm_vchat_convs_' . $me . '_' . $type);
    wp_send_json_success();
}
add_action( 'wp_ajax_wcfm_vchat_search_vendors', 'wcfm_vchat_search_vendors' );
function wcfm_vchat_search_vendors(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    $q = isset( $_POST['q'] ) ? sanitize_text_field( $_POST['q'] ) : '';
    $args = array( 'role__in' => array('wcfm_vendor','seller','vendor'), 'number' => 30 );
    if ( $q ) $args['search'] = '*'.esc_attr( $q ).'*';
    $users = get_users( $args );
    $data = array();
    foreach( $users as $u ){
        if ( $u->ID == get_current_user_id() ) continue;
        $store_name = '';
        $store_url = wcfm_vchat_get_store_url( $u->ID ); // FIXED: Always fetch
        if ( function_exists( 'wcfmmp_get_store' ) ) {
            $store = wcfmmp_get_store( $u->ID );
            if ( $store && isset( $store->store_name ) ) $store_name = $store->store_name;
        }
        $data[] = array( 'ID'=>$u->ID, 'display_name'=>$u->display_name, 'store_name'=>$store_name, 'store_url' => $store_url, 'avatar'=>get_avatar_url( $u->ID, array( 'size' => 40 ) ) );
    }
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_wcfm_vchat_get_conversations', 'wcfm_vchat_get_conversations' );
function wcfm_vchat_get_conversations(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $tab = isset($_POST['tab'])? sanitize_text_field($_POST['tab'] ) : 'individual';
    $cache_key = 'wcfm_vchat_convs_' . $me . '_' . $tab;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
    }
    $is_mobile = function_exists('wp_is_mobile') && wp_is_mobile();
    $limit = $is_mobile ? 20 : 50;
    $time_limit = date('Y-m-d H:i:s', strtotime('-10 years')); // FIXED: Extended to 10 years to show older chats
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    $groups_table = $wpdb->prefix . 'wcfm_vchat_groups';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $items = array();
    if ( $tab == 'individual' ){
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT s.other_id, s.last_at, m.content as preview
            FROM (
                SELECT CASE WHEN sender_id=%d THEN receiver_id ELSE sender_id END as other_id, MAX(created_at) as last_at, MAX(id) as last_id
                FROM $messages_table
                WHERE ((sender_id=%d OR receiver_id=%d) AND is_group=0 AND created_at >= %s)
                GROUP BY other_id
            ) s
            JOIN $messages_table m ON m.id = s.last_id
            ORDER BY s.last_at DESC LIMIT %d
        ", $me, $me, $me, $time_limit, $limit ) );
        foreach( $rows as $r ){
            if ( !$r->other_id ) continue;
            $u = get_userdata( $r->other_id );
            if (!$u) continue;
            // FIXED: Use preview parsing for attachments
            $preview = wcfm_vchat_get_preview( $r->preview );
            $last_read = wcfm_vchat_get_last_read( $me, 'individual', $r->other_id );
            $unread = $wpdb->get_var( $wpdb->prepare("
                SELECT COUNT(*) FROM $messages_table
                WHERE ((sender_id=%d AND receiver_id=%d) OR (sender_id=%d AND receiver_id=%d))
                AND is_group=0 AND created_at > %s
            ", $me, $r->other_id, $r->other_id, $me, $last_read ) );
            $store_name = '';
            $store_url = wcfm_vchat_get_store_url( $r->other_id ); // FIXED: Always fetch
            if ( function_exists( 'wcfmmp_get_store' ) ) {
                $store = wcfmmp_get_store( $r->other_id );
                if ( $store && isset( $store->store_name ) ) $store_name = $store->store_name;
            }
            $items[] = array( 'type'=>'individual', 'id'=>$r->other_id, 'title'=>$u->display_name, 'preview'=>$preview, 'avatar'=>get_avatar_url( $r->other_id, array( 'size' => 48 ) ), 'unread_count' => (int)$unread, 'store_name' => $store_name, 'store_url' => $store_url );
        }
    } else {
        $rows = $wpdb->get_results( $wpdb->prepare("
            SELECT g.id,g.name, (SELECT COUNT(*) FROM $participants pp WHERE pp.group_id=g.id) as member_count,
            COALESCE(s.last_at, g.created_at) as last_at, m.content as preview
            FROM $groups_table g
            INNER JOIN $participants p ON p.group_id=g.id
            LEFT JOIN (
                SELECT group_id, MAX(created_at) as last_at, MAX(id) as last_id
                FROM $messages_table
                WHERE is_group=1 AND created_at >= %s
                GROUP BY group_id
            ) s ON s.group_id = g.id
            LEFT JOIN $messages_table m ON m.id = s.last_id
            WHERE p.user_id=%d
            ORDER BY last_at DESC LIMIT %d
        ", $time_limit, $me, $limit ) );
        foreach( $rows as $r ){
            // FIXED: Use preview parsing for attachments
            $preview = wcfm_vchat_get_preview( $r->preview );
            $last_read = wcfm_vchat_get_last_read( $me, 'group', $r->id );
            $unread = $wpdb->get_var( $wpdb->prepare("
                SELECT COUNT(*) FROM $messages_table
                WHERE group_id=%d AND is_group=1 AND created_at > %s
            ", $r->id, $last_read ) );
            $items[] = array( 'type'=>'group', 'id'=>$r->id, 'title'=>$r->name, 'preview'=>$preview, 'avatar'=>'', 'member_count' => $r->member_count, 'unread_count' => (int)$unread );
        }
    }
    set_transient($cache_key, $items, 300); // 5 minutes
    wp_send_json_success( $items );
}
add_action( 'wp_ajax_wcfm_vchat_fetch_messages', 'wcfm_vchat_fetch_messages' );
function wcfm_vchat_fetch_messages(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $type = isset($_POST['type'])? sanitize_text_field($_POST['type']) : 'individual';
    $id = isset($_POST['id'])? intval($_POST['id']) : 0;
    $last_id = isset($_POST['last_id']) ? intval($_POST['last_id']) : 0;
    $time_limit = date('Y-m-d H:i:s', strtotime('-10 years')); // FIXED: Extended to 10 years to show older messages
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $where = " AND created_at >= %s";
    $params = array($time_limit);
    if ($last_id > 0) {
        $where .= " AND id > %d";
        $params[] = $last_id;
    }
    if ( $type == 'individual' ){
        $other = $id;
        if ( ! $other ) wp_send_json_success(array());
        $blocked = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_vchat_blocks WHERE (blocker_id=%d AND blocked_id=%d) OR (blocker_id=%d AND blocked_id=%d)", $me, $other, $other, $me ) );
        if ( $blocked ) wp_send_json_error('blocked');
        // FIXED: ORDER BY ASC for oldest first; supports last_id
        $rows = $wpdb->get_results( $wpdb->prepare("SELECT m.*, u.display_name as sender_name, u.user_email FROM $messages_table m LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID WHERE ((sender_id=%d AND receiver_id=%d) OR (sender_id=%d AND receiver_id=%d))" . $where . " ORDER BY m.created_at ASC LIMIT 200", array_merge(array($me, $other, $other, $me), $params) ) );
    } else {
        $group_id = $id;
        $p = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $participants WHERE group_id=%d AND user_id=%d", $group_id, $me ) );
        if ( ! $p ) wp_send_json_error('not_in_group');
        // FIXED: ORDER BY ASC for oldest first; supports last_id
        $rows = $wpdb->get_results( $wpdb->prepare("SELECT m.*, u.display_name as sender_name, u.user_email FROM $messages_table m LEFT JOIN {$wpdb->users} u ON m.sender_id = u.ID WHERE m.group_id=%d" . $where . " ORDER BY m.created_at ASC LIMIT 200", array_merge(array($group_id), $params) ) );
    }
    $data = array();
    foreach( $rows as $r ){
        $avatar = get_avatar_url( $r->user_email ? $r->user_email : $r->sender_id, array( 'size' => 32 ) );
        $data[] = array( 'id'=>$r->id, 'sender_id'=>$r->sender_id, 'receiver_id'=>$r->receiver_id, 'content'=>wp_kses_post( $r->content ), 'created_at'=>$r->created_at, 'sender_name'=>$r->sender_name, 'sender_avatar'=>$avatar );
    }
    wp_send_json_success( $data );
}
add_action( 'wp_ajax_wcfm_vchat_send_message', 'wcfm_vchat_send_message' );
function wcfm_vchat_send_message(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $type = isset($_POST['type'])? sanitize_text_field($_POST['type']) : 'individual';
    $id = isset($_POST['id'])? intval($_POST['id']) : 0;
    $content = isset($_POST['content'])? wp_kses_post( $_POST['content'] ) : '';
    if ( ! $content ) wp_send_json_error('empty');
    $messages_table = $wpdb->prefix . 'wcfm_vchat_messages';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    if ( $type == 'individual' ){
        $other = $id;
        $blocked = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wcfm_vchat_blocks WHERE (blocker_id=%d AND blocked_id=%d) OR (blocker_id=%d AND blocked_id=%d)", $me, $other, $other, $me ) );
        if ( $blocked ) wp_send_json_error('blocked');
        $wpdb->insert( $messages_table, array( 'group_id'=>null, 'sender_id'=>$me, 'receiver_id'=>$other, 'content'=>$content, 'is_group'=>0 ), array( '%d','%d','%d','%s','%d' ) );
        // Invalidate individual caches
        delete_transient('wcfm_vchat_convs_' . $me . '_individual');
        delete_transient('wcfm_vchat_convs_' . $other . '_individual');
    } else {
        $group_id = $id;
        $p = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $participants WHERE group_id=%d AND user_id=%d", $group_id, $me ) );
        if ( ! $p ) wp_send_json_error('not_in_group');
        $wpdb->insert( $messages_table, array( 'group_id'=>$group_id, 'sender_id'=>$me, 'receiver_id'=>null, 'content'=>$content, 'is_group'=>1 ), array( '%d','%d','%d','%s','%d' ) );
        // Invalidate group caches
        delete_transient('wcfm_vchat_convs_' . $me . '_groups');
        $group_members = $wpdb->get_col($wpdb->prepare("SELECT user_id FROM $participants WHERE group_id=%d", $group_id));
        foreach ($group_members as $member) {
            delete_transient('wcfm_vchat_convs_' . $member . '_groups');
        }
    }
    wp_send_json_success( array('sent'=>true) );
}
add_action( 'wp_ajax_wcfm_vchat_block_vendor', 'wcfm_vchat_block_vendor' );
function wcfm_vchat_block_vendor(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $blocked = isset($_POST['blocked_id'])? intval($_POST['blocked_id']) : 0;
    if ( !$blocked ) wp_send_json_error('bad');
    $table = $wpdb->prefix . 'wcfm_vchat_blocks';
    $wpdb->replace( $table, array( 'blocker_id'=>$me, 'blocked_id'=>$blocked ), array( '%d','%d' ) );
    // Invalidate caches
    delete_transient('wcfm_vchat_convs_' . $me . '_individual');
    delete_transient('wcfm_vchat_convs_' . $blocked . '_individual');
    wp_send_json_success();
}
add_action( 'wp_ajax_wcfm_vchat_exit_group', 'wcfm_vchat_exit_group' );
function wcfm_vchat_exit_group(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
    if ( ! $group_id ) wp_send_json_error( 'invalid' );
    $all_group_id = get_option( 'wcfm_vchat_all_vendors_group_id' );
    if ( $group_id == $all_group_id ) wp_send_json_error( 'cannot_exit_all' );
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $groups_table = $wpdb->prefix . 'wcfm_vchat_groups';
    $wpdb->delete( $participants, array( 'group_id' => $group_id, 'user_id' => $me ) );
    $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $participants WHERE group_id = %d", $group_id ) );
    if ( $count == 0 ) {
        $wpdb->delete( $groups_table, array( 'id' => $group_id ) );
    }
    // Invalidate caches
    delete_transient('wcfm_vchat_convs_' . $me . '_groups');
    wp_send_json_success();
}
add_action( 'wp_ajax_wcfm_vchat_create_group_with_members', 'wcfm_vchat_create_group_with_members' );
function wcfm_vchat_create_group_with_members(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $me = get_current_user_id();
    $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
    $members = isset( $_POST['members'] ) ? array_map( 'intval', (array) $_POST['members'] ) : array();
    if ( strlen( $name ) < 3 || empty( $members ) ) wp_send_json_error( 'invalid' );
    $groups_table = $wpdb->prefix . 'wcfm_vchat_groups';
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $wpdb->insert( $groups_table, array( 'name' => $name, 'created_by' => $me ) );
    $group_id = $wpdb->insert_id;
    if ( ! $group_id ) wp_send_json_error( 'db_error' );
    $wpdb->insert( $participants, array( 'group_id' => $group_id, 'user_id' => $me ) );
    foreach ( $members as $uid ) {
        if ( $uid != $me && wcfm_vchat_is_vendor( $uid ) ) {
            $wpdb->insert( $participants, array( 'group_id' => $group_id, 'user_id' => $uid ) );
        }
    }
    $member_count = count($members) + 1; // + self
    // Invalidate caches for members
    delete_transient('wcfm_vchat_convs_' . $me . '_groups');
    foreach ($members as $uid) {
        delete_transient('wcfm_vchat_convs_' . $uid . '_groups');
    }
    wp_send_json_success( array( 'id' => $group_id, 'name' => $name, 'member_count' => $member_count ) );
}
add_action( 'wp_ajax_wcfm_vchat_get_group_members', 'wcfm_vchat_get_group_members' );
function wcfm_vchat_get_group_members(){
    check_ajax_referer( 'wcfm_vchat_nonce', 'nonce' );
    if ( ! is_user_logged_in() || ! wcfm_vchat_is_vendor() ) wp_send_json_error('not_allowed');
    global $wpdb;
    $group_id = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
    if ( ! $group_id ) wp_send_json_error( 'invalid' );
    $participants = $wpdb->prefix . 'wcfm_vchat_participants';
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT p.user_id, u.display_name, u.user_email FROM $participants p JOIN {$wpdb->users} u ON p.user_id = u.ID WHERE p.group_id = %d", $group_id ) );
    $data = array();
    foreach( $rows as $r ){
        $store_name = '';
        $store_url = wcfm_vchat_get_store_url( $r->user_id ); // FIXED: Always fetch
        if ( function_exists( 'wcfmmp_get_store' ) ) {
            $store = wcfmmp_get_store( $r->user_id );
            if ( $store && isset( $store->store_name ) ) $store_name = $store->store_name;
        }
        $avatar = get_avatar_url( $r->user_email ? $r->user_email : $r->user_id, array( 'size' => 40 ) );
        $data[] = array( 'ID'=>$r->user_id, 'display_name'=>$r->display_name, 'store_name'=>$store_name, 'store_url' => $store_url, 'avatar'=>$avatar );
    }
    wp_send_json_success( $data );
}
// ADDED: Error logging hook for all AJAX (check WP debug log)
add_action('wp_ajax_nopriv_wcfm_vchat_*', function() { error_log('WCFM VChat AJAX called without login'); });
/**
 * NEW: WebSocket server stub - For full implementation, use a service like Pusher or install Ratchet on your server.
 * Broadcast new messages/images via WS for real-time updates across clients.
 * Example: On send_message success, broadcast to relevant users/groups.
 * Note: This requires a separate WS server; the client JS stub connects but needs server-side handling.
 * For Ratchet example: Install composer require cboden/ratchet, then create a server.php with Chat class broadcasting to channels based on convo_id.
 */
?>