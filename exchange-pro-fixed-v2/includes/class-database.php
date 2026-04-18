<?php
/**
 * Database Handler Class
 * Manages all database operations for the exchange system
 */

if (!defined('ABSPATH')) {
    exit;
}

class Exchange_Pro_Database {
    
    private static $instance = null;

    /**
     * NOTE:
     * Several admin screens directly reference `$db->wpdb` and table names.
     * These MUST be accessible or those screens will crash (fatal errors).
     * We keep them public for backwards compatibility.
     */
    public $wpdb;
    public $devices_table;
    public $brands_table;
    public $models_table;
    public $variants_table;
    public $pricing_table;
    public $categories_table;
    public $pincodes_table;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->devices_table = $wpdb->prefix . 'exchange_devices';
        $this->brands_table = $wpdb->prefix . 'exchange_brands';
        $this->models_table = $wpdb->prefix . 'exchange_models';
        $this->variants_table = $wpdb->prefix . 'exchange_variants';
        $this->pricing_table = $wpdb->prefix . 'exchange_pricing';
        $this->categories_table = $wpdb->prefix . 'exchange_categories';
        $this->pincodes_table = $wpdb->prefix . 'exchange_pincodes';
        $this->logs_table = $wpdb->prefix . 'exchange_logs';
    }
    
    public function create_tables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Categories table
        $sql_categories = "CREATE TABLE {$this->categories_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            description text,
            icon varchar(50),
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        // Brands table
        $sql_brands = "CREATE TABLE {$this->brands_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            category_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            logo varchar(255),
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id)
        ) $charset_collate;";
        
        // Models table
        $sql_models = "CREATE TABLE {$this->models_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            brand_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            image varchar(255),
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY brand_id (brand_id)
        ) $charset_collate;";
        
        // Variants table
        $sql_variants = "CREATE TABLE {$this->variants_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            model_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            slug varchar(100) NOT NULL,
            specifications text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY model_id (model_id)
        ) $charset_collate;";
        
        // Pricing table (condition-based pricing)
        $sql_pricing = "CREATE TABLE {$this->pricing_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            variant_id bigint(20) NOT NULL,
            condition_type varchar(50) NOT NULL,
            price decimal(10,2) NOT NULL DEFAULT 0.00,
            description text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY variant_id (variant_id),
            KEY condition_type (condition_type),
            UNIQUE KEY variant_condition (variant_id, condition_type)
        ) $charset_collate;";
        
        // Devices table (exchanged devices log)
        $sql_devices = "CREATE TABLE {$this->devices_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            category_id bigint(20),
            brand_id bigint(20),
            model_id bigint(20),
            variant_id bigint(20),
            condition_type varchar(50),
            imei_serial varchar(100),
            pincode varchar(10),
            exchange_value decimal(10,2) NOT NULL DEFAULT 0.00,
            device_data text,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY product_id (product_id)
        ) $charset_collate;";
        
        // Pincodes table
        $sql_pincodes = "CREATE TABLE {$this->pincodes_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pincode varchar(10) NOT NULL,
            city varchar(100),
            state varchar(100),
            serviceable tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pincode (pincode)
        ) $charset_collate;";
        
        
        // Logs table (audit trail)
        $sql_logs = "CREATE TABLE {$this->logs_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exchange_id bigint(20) DEFAULT NULL,
            order_id bigint(20) DEFAULT NULL,
            action varchar(50) NOT NULL,
            old_value text,
            new_value text,
            admin_user bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY exchange_id (exchange_id),
            KEY order_id (order_id),
            KEY action (action)
        ) $charset_collate;";

dbDelta($sql_categories);
        dbDelta($sql_brands);
        dbDelta($sql_models);
        dbDelta($sql_variants);
        dbDelta($sql_pricing);
        dbDelta($sql_devices);
        dbDelta($sql_pincodes);
        dbDelta($sql_logs);
        
        // Seed demo data ONLY if explicitly enabled.
        // This prevents "sample" data showing up before admin config is complete.
        $seed_demo = get_option('exchange_pro_seed_demo_data', 'no') === 'yes';
        if ($seed_demo) {
            $this->insert_default_categories();
            $this->insert_default_pincodes();
        }
        
        update_option('exchange_pro_db_version', EXCHANGE_PRO_VERSION);
    }
    
    private function insert_default_categories() {
        $categories = array(
            array('name' => 'Mobile', 'slug' => 'mobile', 'icon' => 'smartphone'),
            array('name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'laptop'),
            array('name' => 'Tablet', 'slug' => 'tablet', 'icon' => 'tablet'),
            array('name' => 'Printer', 'slug' => 'printer', 'icon' => 'printer'),
            array('name' => 'Camera', 'slug' => 'camera', 'icon' => 'camera'),
        );
        
        foreach ($categories as $cat) {
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->categories_table} WHERE slug = %s",
                    $cat['slug']
                )
            );
            
            if (!$exists) {
                $this->wpdb->insert(
                    $this->categories_table,
                    $cat,
                    array('%s', '%s', '%s')
                );
            }
        }
    }
    
    private function insert_default_pincodes() {
        // Add some Kerala pincodes
        $pincodes = array(
            array('pincode' => '683101', 'city' => 'Alangad', 'state' => 'Kerala', 'serviceable' => 1),
            array('pincode' => '682001', 'city' => 'Ernakulam', 'state' => 'Kerala', 'serviceable' => 1),
            array('pincode' => '695001', 'city' => 'Thiruvananthapuram', 'state' => 'Kerala', 'serviceable' => 1),
            array('pincode' => '673001', 'city' => 'Kozhikode', 'state' => 'Kerala', 'serviceable' => 1),
            array('pincode' => '680001', 'city' => 'Thrissur', 'state' => 'Kerala', 'serviceable' => 1),
        );
        
        foreach ($pincodes as $pin) {
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT id FROM {$this->pincodes_table} WHERE pincode = %s",
                    $pin['pincode']
                )
            );
            
            if (!$exists) {
                $this->wpdb->insert(
                    $this->pincodes_table,
                    $pin,
                    array('%s', '%s', '%s', '%d')
                );
            }
        }
    }
    
    // CRUD operations for categories
    public function get_categories($status = 'active') {
        $where = $status ? $this->wpdb->prepare("WHERE status = %s", $status) : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->categories_table} {$where} ORDER BY name ASC");
    }
    
    public function get_category($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->categories_table} WHERE id = %d", $id));
    }
    
    public function insert_category($data) {
        return $this->wpdb->insert($this->categories_table, $data);
    }
    
    public function update_category($id, $data) {
        return $this->wpdb->update($this->categories_table, $data, array('id' => $id));
    }
    
    public function delete_category($id) {
        return $this->wpdb->delete($this->categories_table, array('id' => $id));
    }
    
    // CRUD operations for brands
    public function get_brands($category_id = null, $status = 'active') {
        $where = array();
        if ($category_id) {
            $where[] = $this->wpdb->prepare("category_id = %d", $category_id);
        }
        if ($status) {
            $where[] = $this->wpdb->prepare("status = %s", $status);
        }
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->brands_table} {$where_clause} ORDER BY name ASC");
    }
    
    public function get_brand($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->brands_table} WHERE id = %d", $id));
    }
    
    public function insert_brand($data) {
        return $this->wpdb->insert($this->brands_table, $data);
    }
    
    public function update_brand($id, $data) {
        return $this->wpdb->update($this->brands_table, $data, array('id' => $id));
    }
    
    public function delete_brand($id) {
        return $this->wpdb->delete($this->brands_table, array('id' => $id));
    }
    
    // CRUD operations for models
    public function get_models($brand_id = null, $status = 'active') {
        $where = array();
        if ($brand_id) {
            $where[] = $this->wpdb->prepare("brand_id = %d", $brand_id);
        }
        if ($status) {
            $where[] = $this->wpdb->prepare("status = %s", $status);
        }
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->models_table} {$where_clause} ORDER BY name ASC");
    }
    
    public function get_model($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->models_table} WHERE id = %d", $id));
    }
    
    public function insert_model($data) {
        return $this->wpdb->insert($this->models_table, $data);
    }
    
    public function update_model($id, $data) {
        return $this->wpdb->update($this->models_table, $data, array('id' => $id));
    }
    
    public function delete_model($id) {
        return $this->wpdb->delete($this->models_table, array('id' => $id));
    }
    
    // CRUD operations for variants
    public function get_variants($model_id = null, $status = 'active') {
        $where = array();
        if ($model_id) {
            $where[] = $this->wpdb->prepare("model_id = %d", $model_id);
        }
        if ($status) {
            $where[] = $this->wpdb->prepare("status = %s", $status);
        }
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->variants_table} {$where_clause} ORDER BY name ASC");
    }
    
    public function get_variant($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->variants_table} WHERE id = %d", $id));
    }
    
    public function insert_variant($data) {
        return $this->wpdb->insert($this->variants_table, $data);
    }
    
    public function update_variant($id, $data) {
        return $this->wpdb->update($this->variants_table, $data, array('id' => $id));
    }
    
    public function delete_variant($id) {
        return $this->wpdb->delete($this->variants_table, array('id' => $id));
    }
    
    // CRUD operations for pricing
    public function get_pricing($variant_id = null, $condition = null) {
        $where = array();
        if ($variant_id) {
            $where[] = $this->wpdb->prepare("variant_id = %d", $variant_id);
        }
        if ($condition) {
            $where[] = $this->wpdb->prepare("condition_type = %s", $condition);
        }
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        return $this->wpdb->get_results("SELECT * FROM {$this->pricing_table} {$where_clause}");
    }
    
    public function get_price($variant_id, $condition) {
        $result = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->pricing_table} WHERE variant_id = %d AND condition_type = %s",
            $variant_id,
            $condition
        ));
        return $result ? $result->price : 0;
    }
    
    public function insert_pricing($data) {
        return $this->wpdb->insert($this->pricing_table, $data);
    }
    
    public function update_pricing($id, $data) {
        return $this->wpdb->update($this->pricing_table, $data, array('id' => $id));
    }
    
    public function upsert_pricing($variant_id, $condition, $price) {
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM {$this->pricing_table} WHERE variant_id = %d AND condition_type = %s",
            $variant_id,
            $condition
        ));
        
        if ($existing) {
            return $this->wpdb->update(
                $this->pricing_table,
                array('price' => $price),
                array('id' => $existing->id)
            );
        } else {
            return $this->wpdb->insert(
                $this->pricing_table,
                array(
                    'variant_id' => $variant_id,
                    'condition_type' => $condition,
                    'price' => $price
                )
            );
        }
    }
    
    public function delete_pricing($id) {
        return $this->wpdb->delete($this->pricing_table, array('id' => $id));
    }
    
    // Exchange devices operations
    public function insert_exchange_device($data) {
        return $this->wpdb->insert($this->devices_table, $data);
    }
    
    public function get_exchange_devices($order_id = null, $limit = null) {
        $limit_sql = '';
        if ($limit !== null) {
            $limit = intval($limit);
            if ($limit > 0) {
                $limit_sql = ' LIMIT ' . $limit;
            }
        }
        if ($order_id) {
            return $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM {$this->devices_table} WHERE order_id = %d ORDER BY created_at DESC{$limit_sql}",
                $order_id
            ));
        }
        return $this->wpdb->get_results("SELECT * FROM {$this->devices_table} ORDER BY created_at DESC{$limit_sql}");
    }
    
    public function get_exchange_device($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->devices_table} WHERE id = %d",
            $id
        ));
    }
    

    // Exchange logs operations
    public function insert_exchange_log($data) {
        return $this->wpdb->insert($this->logs_table, $data);
    }

    public function get_exchange_logs($limit = 50) {
        $limit = intval($limit);
        if ($limit <= 0) $limit = 50;
        return $this->wpdb->get_results("SELECT * FROM {$this->logs_table} ORDER BY created_at DESC LIMIT {$limit}");
    }

    public function update_exchange_device_status($exchange_id, $status) {
        return $this->wpdb->update($this->devices_table, array('status' => $status), array('id' => $exchange_id));
    }

    public function delete_pricing_by_variant($variant_id) {
        return $this->wpdb->delete($this->pricing_table, array('variant_id' => $variant_id));
    }
    // Pincode operations
    public function check_pincode($pincode) {
        $result = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->pincodes_table} WHERE pincode = %s",
            $pincode
        ));
        return $result && $result->serviceable ? true : false;
    }
    
    public function get_pincodes() {
        return $this->wpdb->get_results("SELECT * FROM {$this->pincodes_table} ORDER BY pincode ASC");
    }
    
    public function insert_pincode($data) {
        return $this->wpdb->insert($this->pincodes_table, $data);
    }
    
    public function update_pincode($id, $data) {
        return $this->wpdb->update($this->pincodes_table, $data, array('id' => $id));
    }
    
    public function delete_pincode($id) {
        return $this->wpdb->delete($this->pincodes_table, array('id' => $id));
    }
    
    public function bulk_insert_pincodes($pincodes) {
        $values = array();
        $placeholders = array();
        
        foreach ($pincodes as $pin) {
            $placeholders[] = "(%s, %s, %s, %d)";
            $values[] = $pin['pincode'];
            $values[] = isset($pin['city']) ? $pin['city'] : '';
            $values[] = isset($pin['state']) ? $pin['state'] : '';
            $values[] = isset($pin['serviceable']) ? $pin['serviceable'] : 1;
        }
        
        $query = "INSERT INTO {$this->pincodes_table} (pincode, city, state, serviceable) VALUES ";
        $query .= implode(', ', $placeholders);
        $query .= " ON DUPLICATE KEY UPDATE city = VALUES(city), state = VALUES(state), serviceable = VALUES(serviceable)";
        
        return $this->wpdb->query($this->wpdb->prepare($query, $values));
    }
    
    // Get complete device hierarchy
    public function get_device_hierarchy() {
        $categories = $this->get_categories();
        $result = array();
        
        foreach ($categories as $category) {
            $cat_data = array(
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'brands' => array()
            );
            
            $brands = $this->get_brands($category->id);
            foreach ($brands as $brand) {
                $brand_data = array(
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'models' => array()
                );
                
                $models = $this->get_models($brand->id);
                foreach ($models as $model) {
                    $model_data = array(
                        'id' => $model->id,
                        'name' => $model->name,
                        'slug' => $model->slug,
                        'variants' => array()
                    );
                    
                    $variants = $this->get_variants($model->id);
                    foreach ($variants as $variant) {
                        $pricing = $this->get_pricing($variant->id);
                        $prices = array();
                        foreach ($pricing as $p) {
                            $prices[$p->condition_type] = $p->price;
                        }
                        
                        $model_data['variants'][] = array(
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'slug' => $variant->slug,
                            'pricing' => $prices
                        );
                    }
                    
                    $brand_data['models'][] = $model_data;
                }
                
                $cat_data['brands'][] = $brand_data;
            }
            
            $result[] = $cat_data;
        }
        
        return $result;
    }

    /**
     * Pricing Matrix (AUTO)
     * Returns one row per Variant with condition columns.
     * Source of truth: categories/brands/models/variants + pricing table.
     */
    public function get_pricing_matrix($category_id = 0) {
        $where = "WHERE c.status = 'active' AND b.status = 'active' AND m.status = 'active' AND v.status = 'active'";
        $args = array();
        if (!empty($category_id)) {
            $where .= " AND c.id = %d";
            $args[] = intval($category_id);
        }

        $sql = "
            SELECT
                c.id AS category_id,
                c.name AS category_name,
                b.id AS brand_id,
                b.name AS brand_name,
                m.id AS model_id,
                m.name AS model_name,
                v.id AS variant_id,
                v.name AS variant_name,
                COALESCE(MAX(CASE WHEN p.condition_type = 'excellent' THEN p.price END), 0) AS excellent_price,
                COALESCE(MAX(CASE WHEN p.condition_type = 'good' THEN p.price END), 0) AS good_price,
                COALESCE(MAX(CASE WHEN p.condition_type = 'fair' THEN p.price END), 0) AS fair_price,
                COALESCE(MAX(CASE WHEN p.condition_type = 'poor' THEN p.price END), 0) AS poor_price
            FROM {$this->variants_table} v
            INNER JOIN {$this->models_table} m ON m.id = v.model_id
            INNER JOIN {$this->brands_table} b ON b.id = m.brand_id
            INNER JOIN {$this->categories_table} c ON c.id = b.category_id
            LEFT JOIN {$this->pricing_table} p
                ON p.variant_id = v.id AND p.status = 'active'
            {$where}
            GROUP BY v.id
            ORDER BY c.name ASC, b.name ASC, m.name ASC, v.name ASC
        ";

        if (!empty($args)) {
            return $this->wpdb->get_results($this->wpdb->prepare($sql, $args));
        }
        return $this->wpdb->get_results($sql);
    }
}
