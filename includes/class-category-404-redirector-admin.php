<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.facebook.com/geekishrahul
 * @since      0.3
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/admin
 * @author     Rahul Rauniyar
 */
class Category_404_Redirector_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.3
     */
    public function __construct() {
    }

    /**
     * Handle debug settings form submission.
     *
     * @since    0.3
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register AJAX handlers
        add_action('wp_ajax_save_category_mapping', array($this, 'ajax_save_category_mapping'));
        add_action('wp_ajax_save_popup_settings', array($this, 'ajax_save_popup_settings'));
        add_action('wp_ajax_category_404_redirector_check_url', array($this, 'ajax_check_url'));
        
        // Handle debug settings form submission
        add_action('admin_init', array($this, 'handle_debug_settings'));
    }
    
    /**
     * Handle debug settings form submission.
     *
     * @since    0.3
     */
    public function handle_debug_settings() {
        if (isset($_POST['save_debug']) && isset($_POST['category_404_redirector_debug_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_debug_nonce'], 'category_404_redirector_save_debug')) {
            
            // Save debug settings
            update_option('category_404_redirector_debug_mode', isset($_POST['debug_mode']) ? 1 : 0);
            update_option('category_404_redirector_force_popup', isset($_POST['force_popup']) ? 1 : 0);
            
            // Add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Debug settings saved successfully.</p></div>';
            });
        }
    }

    /**
     * Register the admin menu.
     *
     * @since    0.3
     */
    public function add_admin_menu() {
        // Add main menu item
        add_menu_page(
            'Category 404 Redirector',
            'Category 404 Redirector',
            'manage_options',
            'category-404-redirector',
            array($this, 'render_settings_page'),
            'dashicons-admin-links',
            30
        );
        
        // Add submenu items
        add_submenu_page(
            'category-404-redirector',
            'Category Mapping',
            'Category Mapping',
            'manage_options',
            'category-404-redirector',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'category-404-redirector',
            'Popup Settings',
            'Popup Settings',
            'manage_options',
            'category-404-redirector-popups',
            array($this, 'render_popup_settings_page')
        );
    }

    /**
     * Register the JavaScript and CSS for the admin area.
     *
     * @since    0.3
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts($hook) {
        // Check if we're on our plugin's pages
        if (strpos($hook, 'category-404-redirector') === false) {
            return;
        }
        
        // Add WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Add media uploader
        wp_enqueue_media();
        
        // Add custom admin script
        wp_enqueue_script(
            'category-404-redirector-admin',
            CATEGORY_404_REDIRECTOR_URL . 'js/admin.js',
            array('jquery', 'wp-color-picker'),
            CATEGORY_404_REDIRECTOR_VERSION . '.' . time(), // Add timestamp to prevent caching
            true
        );
        
        // Add custom admin styles
        wp_enqueue_style(
            'category-404-redirector-admin-style',
            CATEGORY_404_REDIRECTOR_URL . 'css/admin.css',
            array(),
            CATEGORY_404_REDIRECTOR_VERSION . '.' . time() // Add timestamp to prevent caching
        );
        
        // Add popup styles for preview
        wp_enqueue_style(
            'category-404-redirector-popup-style',
            CATEGORY_404_REDIRECTOR_URL . 'css/popup.css',
            array(),
            CATEGORY_404_REDIRECTOR_VERSION . '.' . time() // Add timestamp to prevent caching
        );
        
        // Localize the script with data for AJAX
        wp_localize_script(
            'category-404-redirector-admin',
            'category404RedirectorData',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('category_404_redirector_admin_nonce'),
                'pluginUrl' => CATEGORY_404_REDIRECTOR_URL
            )
        );
    }

    /**
     * AJAX handler for saving category mapping.
     *
     * @since    0.3
     */
    public function ajax_save_category_mapping() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'category_404_redirector_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            exit;
        }
        
        // Parse form data
        parse_str($_POST['formData'], $form_data);
        
        // Save category mappings
        global $wpdb;
        $table_name = $wpdb->prefix . 'category_404_redirector';
        
        if (isset($form_data['category_mapping']) && is_array($form_data['category_mapping'])) {
            foreach ($form_data['category_mapping'] as $category_id => $default_link) {
                $category_id = intval($category_id);
                $default_link = esc_url_raw($default_link);
                
                if (empty($default_link)) {
                    // Delete mapping if link is empty
                    $wpdb->delete(
                        $table_name,
                        array('category_id' => $category_id),
                        array('%d')
                    );
                } else {
                    // Check if mapping exists
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $table_name WHERE category_id = %d",
                        $category_id
                    ));
                    
                    if ($exists) {
                        // Update existing mapping
                        $wpdb->update(
                            $table_name,
                            array('default_link' => $default_link),
                            array('category_id' => $category_id),
                            array('%s'),
                            array('%d')
                        );
                    } else {
                        // Insert new mapping
                        $wpdb->insert(
                            $table_name,
                            array(
                                'category_id' => $category_id,
                                'default_link' => $default_link
                            ),
                            array('%d', '%s')
                        );
                    }
                }
            }
        }
        
        // Save debug settings
        update_option('category_404_redirector_debug_mode', isset($form_data['debug_mode']) ? 1 : 0);
        update_option('category_404_redirector_force_popup', isset($form_data['force_popup']) ? 1 : 0);
        
        // Success response
        wp_send_json_success(array('message' => 'Mapping saved successfully.'));
        exit;
    }

    /**
     * AJAX handler for saving popup settings.
     *
     * @since    0.3
     */
    public function ajax_save_popup_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'category_404_redirector_admin_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
            exit;
        }
        
        // Parse form data
        parse_str($_POST['formData'], $form_data);
        
        // Get category ID
        $category_id = isset($form_data['category_id']) ? intval($form_data['category_id']) : 0;
        if ($category_id <= 0) {
            wp_send_json_error(array('message' => 'Invalid category ID.'));
            exit;
        }
        
        // Prepare data
        $data = array(
            'category_id' => $category_id,
            'enabled' => isset($form_data['popup_enabled']) ? 1 : 0,
            'title' => isset($form_data['popup_title']) ? sanitize_text_field($form_data['popup_title']) : '',
            'subtitle' => isset($form_data['popup_subtitle']) ? sanitize_text_field($form_data['popup_subtitle']) : '',
            'description' => isset($form_data['popup_description']) ? sanitize_textarea_field($form_data['popup_description']) : '',
            'feature_1' => isset($form_data['popup_feature_1']) ? sanitize_text_field($form_data['popup_feature_1']) : '',
            'feature_2' => isset($form_data['popup_feature_2']) ? sanitize_text_field($form_data['popup_feature_2']) : '',
            'feature_3' => isset($form_data['popup_feature_3']) ? sanitize_text_field($form_data['popup_feature_3']) : '',
            'button_primary_text' => isset($form_data['popup_button_primary_text']) ? sanitize_text_field($form_data['popup_button_primary_text']) : '',
            'button_secondary_text' => isset($form_data['popup_button_secondary_text']) ? sanitize_text_field($form_data['popup_button_secondary_text']) : '',
            'footer_text' => isset($form_data['popup_footer_text']) ? sanitize_text_field($form_data['popup_footer_text']) : '',
            'background_color' => isset($form_data['popup_background_color']) ? sanitize_hex_color($form_data['popup_background_color']) : '#e32b2b',
            'header_color' => isset($form_data['popup_header_color']) ? sanitize_hex_color($form_data['popup_header_color']) : '#e32b2b',
            'subtitle_background_color' => isset($form_data['popup_subtitle_background_color']) ? sanitize_hex_color($form_data['popup_subtitle_background_color']) : '#ffc107',
            'button_primary_color' => isset($form_data['popup_button_primary_color']) ? sanitize_hex_color($form_data['popup_button_primary_color']) : '#ffc107',
            'button_primary_text_color' => isset($form_data['popup_button_primary_text_color']) ? sanitize_hex_color($form_data['popup_button_primary_text_color']) : '#000000',
            'button_secondary_color' => isset($form_data['popup_button_secondary_color']) ? sanitize_hex_color($form_data['popup_button_secondary_color']) : '#ffffff',
            'button_secondary_text_color' => isset($form_data['popup_button_secondary_text_color']) ? sanitize_hex_color($form_data['popup_button_secondary_text_color']) : '#000000',
            'content_background_color' => isset($form_data['popup_content_background_color']) ? sanitize_hex_color($form_data['popup_content_background_color']) : '#000000',
            'text_color' => isset($form_data['popup_text_color']) ? sanitize_hex_color($form_data['popup_text_color']) : '#ffffff',
            'countdown_seconds' => isset($form_data['popup_countdown_seconds']) ? intval($form_data['popup_countdown_seconds']) : 20,
            'image_url' => isset($form_data['popup_image_url']) ? esc_url_raw($form_data['popup_image_url']) : '',
            'enable_glare' => isset($form_data['popup_enable_glare']) ? 1 : 0,
            'title_font_size' => isset($form_data['popup_title_font_size']) ? sanitize_text_field($form_data['popup_title_font_size']) : 'medium',
            'subtitle_font_size' => isset($form_data['popup_subtitle_font_size']) ? sanitize_text_field($form_data['popup_subtitle_font_size']) : 'medium',
            'description_font_size' => isset($form_data['popup_description_font_size']) ? sanitize_text_field($form_data['popup_description_font_size']) : 'medium',
            'feature_font_size' => isset($form_data['popup_feature_font_size']) ? sanitize_text_field($form_data['popup_feature_font_size']) : 'medium',
            'button_font_size' => isset($form_data['popup_button_font_size']) ? sanitize_text_field($form_data['popup_button_font_size']) : 'medium',
            'footer_font_size' => isset($form_data['popup_footer_font_size']) ? sanitize_text_field($form_data['popup_footer_font_size']) : 'medium',
            'title_font_size_mobile' => isset($form_data['popup_title_font_size_mobile']) ? sanitize_text_field($form_data['popup_title_font_size_mobile']) : 'medium',
            'subtitle_font_size_mobile' => isset($form_data['popup_subtitle_font_size_mobile']) ? sanitize_text_field($form_data['popup_subtitle_font_size_mobile']) : 'medium',
            'description_font_size_mobile' => isset($form_data['popup_description_font_size_mobile']) ? sanitize_text_field($form_data['popup_description_font_size_mobile']) : 'medium',
            'feature_font_size_mobile' => isset($form_data['popup_feature_font_size_mobile']) ? sanitize_text_field($form_data['popup_feature_font_size_mobile']) : 'medium',
            'button_font_size_mobile' => isset($form_data['popup_button_font_size_mobile']) ? sanitize_text_field($form_data['popup_button_font_size_mobile']) : 'medium',
            'footer_font_size_mobile' => isset($form_data['popup_footer_font_size_mobile']) ? sanitize_text_field($form_data['popup_footer_font_size_mobile']) : 'medium',
            'product_bg_color' => isset($form_data['popup_product_bg_color']) ? sanitize_hex_color($form_data['popup_product_bg_color']) : '#ffffff',
            'product_bg_opacity' => isset($form_data['popup_product_bg_opacity']) ? intval($form_data['popup_product_bg_opacity']) : 20,
            'product_bg_radius' => isset($form_data['popup_product_bg_radius']) ? sanitize_text_field($form_data['popup_product_bg_radius']) : 'none',
            'button_width' => isset($form_data['popup_button_width']) ? intval($form_data['popup_button_width']) : 100,
            'button_style' => isset($form_data['popup_button_style']) ? sanitize_text_field($form_data['popup_button_style']) : 'flat',
            'description_text_color' => isset($form_data['popup_description_text_color']) ? sanitize_hex_color($form_data['popup_description_text_color']) : '#ffffff',
            'bullet_text_color' => isset($form_data['popup_bullet_text_color']) ? sanitize_hex_color($form_data['popup_bullet_text_color']) : '#ffffff',
            'timer_bar_color' => isset($form_data['popup_timer_bar_color']) ? sanitize_hex_color($form_data['popup_timer_bar_color']) : '#ffc107',
        );
        
        // Save to database
        global $wpdb;
        $popup_table = $wpdb->prefix . 'category_404_redirector_popups';
        
        // Check if settings exist for this category
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $popup_table WHERE category_id = %d",
            $category_id
        ));
        
        if ($exists) {
            // Update existing settings
            $wpdb->update(
                $popup_table,
                $data,
                array('category_id' => $category_id),
                array(
                    '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                    '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s'
                ),
                array('%d')
            );
        } else {
            // Insert new settings
            $wpdb->insert(
                $popup_table,
                $data,
                array(
                    '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                    '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                    '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s'
                )
            );
        }
        
        // Success response
        wp_send_json_success(array('message' => 'Popup settings saved successfully.'));
        exit;
    }

    /**
     * AJAX handler to check if a URL exists.
     *
     * @since    0.3
     */
    public function ajax_check_url() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'category-404-redirector-nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            exit;
        }
        
        // Get the URL to check
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error(array('message' => 'No URL provided'));
            exit;
        }
        
        // For now, just return that the URL exists
        wp_send_json_success(array('exists' => true));
        exit;
    }

    /**
     * Render the settings page.
     *
     * @since    0.3
     */
    public function render_settings_page() {
        global $wpdb;
        
        // Get all categories
        $categories = get_categories(array(
            'hide_empty' => false,
        ));
        
        // Get existing mappings
        $table_name = $wpdb->prefix . 'category_404_redirector';
        $mappings = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        
        // Convert to associative array for easier lookup
        $category_mappings = array();
        if ($mappings) {
            foreach ($mappings as $mapping) {
                $category_mappings[$mapping['category_id']] = $mapping['default_link'];
            }
        }
        
        // Get popup settings
        $popup_table = $wpdb->prefix . 'category_404_redirector_popups';
        $popup_settings = $wpdb->get_results("SELECT * FROM $popup_table", ARRAY_A);
        
        // Convert to associative array for easier lookup
        $category_popup_settings = array();
        if ($popup_settings) {
            foreach ($popup_settings as $setting) {
                $category_popup_settings[$setting['category_id']] = $setting['enabled'];
            }
        }
        
        // Get debug settings
        $debug_mode = get_option('category_404_redirector_debug_mode', false);
        $force_popup = get_option('category_404_redirector_force_popup', false);
        
        // Handle form submission
        if (isset($_POST['save_mapping']) && isset($_POST['category_404_redirector_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_nonce'], 'category_404_redirector_save_settings')) {
            
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $default_link = isset($_POST['default_link']) ? esc_url_raw($_POST['default_link']) : '';
            
            if ($category_id > 0 && !empty($default_link)) {
                // Check if mapping exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE category_id = %d",
                    $category_id
                ));
                
                if ($exists) {
                    // Update existing mapping
                    $wpdb->update(
                        $table_name,
                        array('default_link' => $default_link),
                        array('category_id' => $category_id),
                        array('%s'),
                        array('%d')
                    );
                } else {
                    // Insert new mapping
                    $wpdb->insert(
                        $table_name,
                        array(
                            'category_id' => $category_id,
                            'default_link' => $default_link
                        ),
                        array('%d', '%s')
                    );
                }
                
                // Refresh mappings
                $mappings = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
                $category_mappings = array();
                if ($mappings) {
                    foreach ($mappings as $mapping) {
                        $category_mappings[$mapping['category_id']] = $mapping['default_link'];
                    }
                }
                
                echo '<div class="notice notice-success is-dismissible"><p>Category mapping saved successfully.</p></div>';
            }
        }
        
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['category_id']) && 
            isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_mapping_' . $_GET['category_id'])) {
            
            $category_id = intval($_GET['category_id']);
            
            // Delete mapping
            $wpdb->delete(
                $table_name,
                array('category_id' => $category_id),
                array('%d')
            );
            
            // Refresh mappings
            $mappings = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
            $category_mappings = array();
            if ($mappings) {
                foreach ($mappings as $mapping) {
                    $category_mappings[$mapping['category_id']] = $mapping['default_link'];
                }
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>Category mapping deleted successfully.</p></div>';
        }
        
        // Handle import/export
        if (isset($_POST['export_settings']) && isset($_POST['category_404_redirector_export_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_export_nonce'], 'category_404_redirector_export')) {
            
            // Prepare export data
            $export_data = array(
                'mappings' => $mappings,
                'debug_mode' => $debug_mode,
                'force_popup' => $force_popup
            );
            
            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="category-404-redirector-settings.json"');
            
            // Output JSON
            echo json_encode($export_data);
            exit;
        }
        
        // Handle import
        if (isset($_POST['import_settings']) && isset($_POST['category_404_redirector_import_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_import_nonce'], 'category_404_redirector_import')) {
            
            if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
                $import_data = json_decode($file_content, true);
                
                if ($import_data && isset($import_data['mappings'])) {
                    // Import mappings
                    foreach ($import_data['mappings'] as $mapping) {
                        $category_id = intval($mapping['category_id']);
                        $default_link = esc_url_raw($mapping['default_link']);
                        
                        // Check if mapping exists
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $table_name WHERE category_id = %d",
                            $category_id
                        ));
                        
                        if ($exists) {
                            // Update existing mapping
                            $wpdb->update(
                                $table_name,
                                array('default_link' => $default_link),
                                array('category_id' => $category_id),
                                array('%s'),
                                array('%d')
                            );
                        } else {
                            // Insert new mapping
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'category_id' => $category_id,
                                    'default_link' => $default_link
                                ),
                                array('%d', '%s')
                            );
                        }
                    }
                    
                    // Import debug settings
                    if (isset($import_data['debug_mode'])) {
                        update_option('category_404_redirector_debug_mode', $import_data['debug_mode'] ? 1 : 0);
                    }
                    
                    if (isset($import_data['force_popup'])) {
                        update_option('category_404_redirector_force_popup', $import_data['force_popup'] ? 1 : 0);
                    }
                    
                    // Refresh mappings
                    $mappings = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
                    $category_mappings = array();
                    if ($mappings) {
                        foreach ($mappings as $mapping) {
                            $category_mappings[$mapping['category_id']] = $mapping['default_link'];
                        }
                    }
                    
                    echo '<div class="notice notice-success is-dismissible"><p>Settings imported successfully.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>Invalid import file format.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error uploading file.</p></div>';
            }
        }
        
        // Render the page
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=category-404-redirector" class="nav-tab nav-tab-active">Category Mapping</a>
                <a href="?page=category-404-redirector-popups" class="nav-tab">Popup Settings</a>
            </h2>
            
            <div class="category-mapping-container">
                <h3>Add/Edit Category Link Mapping</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('category_404_redirector_save_settings', 'category_404_redirector_nonce'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="category_id">Select Category</label></th>
                                <td>
                                    <select name="category_id" id="category_id">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $category) : ?>
                                            <option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="default_link">Default Link</label></th>
                                <td>
                                    <input type="url" name="default_link" id="default_link" class="regular-text" value="" placeholder="https://example.com/product">
                                    <p class="description">This link will replace any non-existent links, hash links (#), and javascript:void(0) links in posts of the selected category.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="save_mapping" class="button button-primary" value="Save Mapping">
                    </p>
                </form>
                
                <hr>
                
                <h3>Import/Export Settings</h3>
                
                <div class="import-export-container" style="display: flex; gap: 30px;">
                    <div class="export-settings">
                        <h4>Export Settings</h4>
                        <p>Export your category link mappings to a JSON file that you can use to backup or transfer settings to another site.</p>
                        <form method="post" action="">
                            <?php wp_nonce_field('category_404_redirector_export', 'category_404_redirector_export_nonce'); ?>
                            <p>
                                <input type="submit" name="export_settings" class="button" value="Export Settings">
                            </p>
                        </form>
                    </div>
                    
                    <div class="import-settings">
                        <h4>Import Settings</h4>
                        <p>Import category link mappings from a JSON file. This will overwrite any existing mappings with the same category ID.</p>
                        <form method="post" action="" enctype="multipart/form-data">
                            <?php wp_nonce_field('category_404_redirector_import', 'category_404_redirector_import_nonce'); ?>
                            <p>
                                <label for="import_file">Import File</label><br>
                                <input type="file" name="import_file" id="import_file">
                                <p class="description">Select a JSON file exported from Category 404 Redirector.</p>
                            </p>
                            <p>
                                <input type="submit" name="import_settings" class="button" value="Import Settings">
                            </p>
                        </form>
                    </div>
                </div>
                
                <hr>
                
                <h3>Existing Category Link Mappings</h3>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Default Link</th>
                            <th>Popup</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mappings)) : ?>
                            <tr>
                                <td colspan="4">No mappings found.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($mappings as $mapping) : ?>
                                <tr>
                                    <td><?php echo esc_html(get_cat_name($mapping['category_id'])); ?></td>
                                    <td><a href="<?php echo esc_url($mapping['default_link']); ?>" target="_blank"><?php echo esc_url($mapping['default_link']); ?></a></td>
                                    <td>
                                        <?php if (isset($category_popup_settings[$mapping['category_id']]) && $category_popup_settings[$mapping['category_id']]) : ?>
                                            <span class="dashicons dashicons-yes" style="color: green;"></span> Enabled                                        <?php else : ?>
                                            <span class="dashicons dashicons-no" style="color: red;"></span> Disabled
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=category-404-redirector-popups&category_id=' . $mapping['category_id'])); ?>" class="button button-small">Edit Popup</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=category-404-redirector&action=delete&category_id=' . $mapping['category_id']), 'delete_mapping_' . $mapping['category_id'])); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this mapping?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <hr>
                
                <h3>Debug Settings</h3>
                <form method="post" action="" id="category-404-redirector-debug-form">
                    <?php wp_nonce_field('category_404_redirector_save_debug', 'category_404_redirector_debug_nonce'); ?>
                    
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row">Debug Mode</th>
                                <td>
                                    <label for="debug_mode">
                                        <input type="checkbox" name="debug_mode" id="debug_mode" <?php checked($debug_mode); ?>>
                                        Enable debug mode (logs information to the console)
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Force Popup</th>
                                <td>
                                    <label for="force_popup">
                                        <input type="checkbox" name="force_popup" id="force_popup" <?php checked($force_popup); ?>>
                                        Force popup to show for all links (for testing)
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="save_debug" class="button button-primary" value="Save Debug Settings">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render the popup settings page.
     *
     * @since    0.3
     */
    public function render_popup_settings_page() {
        global $wpdb;
        
        // Get all categories
        $categories = get_categories(array(
            'hide_empty' => false,
        ));
        
        // Get the selected category
        $selected_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : (count($categories) > 0 ? $categories[0]->term_id : 0);
        
        // Get popup settings for the selected category
        $popup_table = $wpdb->prefix . 'category_404_redirector_popups';
        $popup_settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $popup_table WHERE category_id = %d", $selected_category_id), ARRAY_A);
        
        // Default values if no settings exist
        if (!$popup_settings) {
            $popup_settings = array(
                'enabled' => 0,
                'title' => 'IMPORTANT NOTICE: REBRANDING UPDATE',
                'subtitle' => 'This Product Is Now Rebranded Due To Counterfeit Concerns.',
                'description' => 'The new packaging comes with advanced security features to ensure authenticity, including:',
                'feature_1' => 'Registered Trademark Protection',
                'feature_2' => 'Embedded Security Pattern',
                'feature_3' => 'Tamper-Evident Seal',
                'button_primary_text' => 'BUY NOW',
                'button_secondary_text' => 'GO BACK',
                'footer_text' => '100% GUARANTEED AUTHENTIC PACKAGING',
                'background_color' => '#e32b2b',
                'header_color' => '#e32b2b',
                'subtitle_background_color' => '#ffc107',
                'button_primary_color' => '#ffc107',
                'button_primary_text_color' => '#000000',
                'button_secondary_color' => '#ffffff',
                'button_secondary_text_color' => '#000000',
                'content_background_color' => '#000000',
                'text_color' => '#ffffff',
                'countdown_seconds' => 20,
                'image_url' => '',
                'enable_glare' => 1,
                'title_font_size' => 'medium',
                'subtitle_font_size' => 'medium',
                'description_font_size' => 'medium',
                'features_font_size' => 'medium',
                'button_font_size' => 'medium',
                'footer_font_size' => 'medium',
                'title_font_size_mobile' => 'medium',
                'subtitle_font_size_mobile' => 'medium',
                'description_font_size_mobile' => 'medium',
                'features_font_size_mobile' => 'medium',
                'button_font_size_mobile' => 'medium',
                'footer_font_size_mobile' => 'medium',
                'product_bg_color' => '#ffffff',
                'product_bg_opacity' => 20,
                'product_bg_radius' => 'none',
                'button_width' => 100,
                'button_style' => 'flat',
                'description_text_color' => '#ffffff',
                'bullet_text_color' => '#ffffff',
                'timer_bar_color' => '#ffc107',
            );
        }
        
        // Font size options
        $font_size_options = array(
            'xsmall' => 'Extra Small',
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Large',
            'xlarge' => 'Extra Large',
        );
        
        // Button style options
        $button_style_options = array(
            'flat' => 'Flat',
            'rounded' => 'Rounded',
            'outlined' => 'Outlined',
            'gradient' => 'Gradient',
            'shadow' => 'Shadow',
            '3d' => '3D',
            'neon' => 'Neon',
            'glossy' => 'Glossy',
            'shiny' => 'Shiny',
            'metallic' => 'Metallic',
            'pill' => 'Pill',
            'skeuomorphic' => 'Skeuomorphic',
        );
        
        // Product background radius options
        $bg_radius_options = array(
            'none' => 'None',
            'small' => 'Small',
            'medium' => 'Medium',
            'large' => 'Circle',
        );
        
        // Handle form submission
        if (isset($_POST['submit']) && isset($_POST['category_404_redirector_popup_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_popup_nonce'], 'category_404_redirector_save_popup')) {
            
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            
            // Prepare data
            $data = array(
                'category_id' => $category_id,
                'enabled' => isset($_POST['popup_enabled']) ? 1 : 0,
                'title' => isset($_POST['popup_title']) ? sanitize_text_field($_POST['popup_title']) : '',
                'subtitle' => isset($_POST['popup_subtitle']) ? sanitize_text_field($_POST['popup_subtitle']) : '',
                'description' => isset($_POST['popup_description']) ? sanitize_textarea_field($_POST['popup_description']) : '',
                'feature_1' => isset($_POST['popup_feature_1']) ? sanitize_text_field($_POST['popup_feature_1']) : '',
                'feature_2' => isset($_POST['popup_feature_2']) ? sanitize_text_field($_POST['popup_feature_2']) : '',
                'feature_3' => isset($_POST['popup_feature_3']) ? sanitize_text_field($_POST['popup_feature_3']) : '',
                'button_primary_text' => isset($_POST['popup_button_primary_text']) ? sanitize_text_field($_POST['popup_button_primary_text']) : '',
                'button_secondary_text' => isset($_POST['popup_button_secondary_text']) ? sanitize_text_field($_POST['popup_button_secondary_text']) : '',
                'footer_text' => isset($_POST['popup_footer_text']) ? sanitize_text_field($_POST['popup_footer_text']) : '',
                'background_color' => isset($_POST['popup_background_color']) ? sanitize_hex_color($_POST['popup_background_color']) : '#e32b2b',
                'header_color' => isset($_POST['popup_header_color']) ? sanitize_hex_color($_POST['popup_header_color']) : '#e32b2b',
                'subtitle_background_color' => isset($_POST['popup_subtitle_background_color']) ? sanitize_hex_color($_POST['popup_subtitle_background_color']) : '#ffc107',
                'button_primary_color' => isset($_POST['popup_button_primary_color']) ? sanitize_hex_color($_POST['popup_button_primary_color']) : '#ffc107',
                'button_primary_text_color' => isset($_POST['popup_button_primary_text_color']) ? sanitize_hex_color($_POST['popup_button_primary_text_color']) : '#000000',
                'button_secondary_color' => isset($_POST['popup_button_secondary_color']) ? sanitize_hex_color($_POST['popup_button_secondary_color']) : '#ffffff',
                'button_secondary_text_color' => isset($_POST['popup_button_secondary_text_color']) ? sanitize_hex_color($_POST['popup_button_secondary_text_color']) : '#000000',
                'content_background_color' => isset($_POST['popup_content_background_color']) ? sanitize_hex_color($_POST['popup_content_background_color']) : '#000000',
                'text_color' => isset($_POST['popup_text_color']) ? sanitize_hex_color($_POST['popup_text_color']) : '#ffffff',
                'countdown_seconds' => isset($_POST['popup_countdown_seconds']) ? intval($_POST['popup_countdown_seconds']) : 20,
                'image_url' => isset($_POST['popup_image_url']) ? esc_url_raw($_POST['popup_image_url']) : '',
                'enable_glare' => isset($_POST['popup_enable_glare']) ? 1 : 0,
                'title_font_size' => isset($_POST['popup_title_font_size']) ? sanitize_text_field($_POST['popup_title_font_size']) : 'medium',
                'subtitle_font_size' => isset($_POST['popup_subtitle_font_size']) ? sanitize_text_field($_POST['popup_subtitle_font_size']) : 'medium',
                'description_font_size' => isset($_POST['popup_description_font_size']) ? sanitize_text_field($_POST['popup_description_font_size']) : 'medium',
                'features_font_size' => isset($_POST['popup_features_font_size']) ? sanitize_text_field($_POST['popup_features_font_size']) : 'medium',
                'button_font_size' => isset($_POST['popup_button_font_size']) ? sanitize_text_field($_POST['popup_button_font_size']) : 'medium',
                'footer_font_size' => isset($_POST['popup_footer_font_size']) ? sanitize_text_field($_POST['popup_footer_font_size']) : 'medium',
                'title_font_size_mobile' => isset($_POST['popup_title_font_size_mobile']) ? sanitize_text_field($_POST['popup_title_font_size_mobile']) : 'medium',
                'subtitle_font_size_mobile' => isset($_POST['popup_subtitle_font_size_mobile']) ? sanitize_text_field($_POST['popup_subtitle_font_size_mobile']) : 'medium',
                'description_font_size_mobile' => isset($_POST['popup_description_font_size_mobile']) ? sanitize_text_field($_POST['popup_description_font_size_mobile']) : 'medium',
                'features_font_size_mobile' => isset($_POST['popup_features_font_size_mobile']) ? sanitize_text_field($_POST['popup_features_font_size_mobile']) : 'medium',
                'button_font_size_mobile' => isset($_POST['popup_button_font_size_mobile']) ? sanitize_text_field($_POST['popup_button_font_size_mobile']) : 'medium',
                'footer_font_size_mobile' => isset($_POST['popup_footer_font_size_mobile']) ? sanitize_text_field($_POST['popup_footer_font_size_mobile']) : 'medium',
                'product_bg_color' => isset($_POST['popup_product_bg_color']) ? sanitize_hex_color($_POST['popup_product_bg_color']) : '#ffffff',
                'product_bg_opacity' => isset($_POST['popup_product_bg_opacity']) ? intval($_POST['popup_product_bg_opacity']) : 20,
                'product_bg_radius' => isset($_POST['popup_product_bg_radius']) ? sanitize_text_field($_POST['popup_product_bg_radius']) : 'none',
                'button_width' => isset($_POST['popup_button_width']) ? intval($_POST['popup_button_width']) : 100,
                'button_style' => isset($_POST['popup_button_style']) ? sanitize_text_field($_POST['popup_button_style']) : 'flat',
                'description_text_color' => isset($_POST['popup_description_text_color']) ? sanitize_hex_color($_POST['popup_description_text_color']) : '#ffffff',
                'bullet_text_color' => isset($_POST['popup_bullet_text_color']) ? sanitize_hex_color($_POST['popup_bullet_text_color']) : '#ffffff',
                'timer_bar_color' => isset($_POST['popup_timer_bar_color']) ? sanitize_hex_color($_POST['popup_timer_bar_color']) : '#ffc107',
            );
            
            // Check if settings exist for this category
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $popup_table WHERE category_id = %d",
                $category_id
            ));
            
            if ($exists) {
                // Update existing settings
                $wpdb->update(
                    $popup_table,
                    $data,
                    array('category_id' => $category_id),
                    array(
                        '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
                    ),
                    array('%d')
                );
            } else {
                // Insert new settings
                $wpdb->insert(
                    $popup_table,
                    $data,
                    array(
                        '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
                    )
                );
            }
            
            // Refresh popup settings
            $popup_settings = $wpdb->get_row($wpdb->prepare("SELECT * FROM $popup_table WHERE category_id = %d", $selected_category_id), ARRAY_A);
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>Popup settings saved successfully.</p></div>';
        }
        
        // Handle reset to defaults
        if (isset($_POST['reset_defaults']) && isset($_POST['category_404_redirector_popup_nonce']) && 
            wp_verify_nonce($_POST['category_404_redirector_popup_nonce'], 'category_404_redirector_save_popup')) {
            
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            
            // Default values
            $default_settings = array(
                'category_id' => $category_id,
                'enabled' => 0,
                'title' => 'IMPORTANT NOTICE: REBRANDING UPDATE',
                'subtitle' => 'This Product Is Now Rebranded Due To Counterfeit Concerns.',
                'description' => 'The new packaging comes with advanced security features to ensure authenticity, including:',
                'feature_1' => 'Registered Trademark Protection',
                'feature_2' => 'Embedded Security Pattern',
                'feature_3' => 'Tamper-Evident Seal',
                'button_primary_text' => 'BUY NOW',
                'button_secondary_text' => 'GO BACK',
                'footer_text' => '100% GUARANTEED AUTHENTIC PACKAGING',
                'background_color' => '#e32b2b',
                'header_color' => '#e32b2b',
                'subtitle_background_color' => '#ffc107',
                'button_primary_color' => '#ffc107',
                'button_primary_text_color' => '#000000',
                'button_secondary_color' => '#ffffff',
                'button_secondary_text_color' => '#000000',
                'content_background_color' => '#000000',
                'text_color' => '#ffffff',
                'countdown_seconds' => 20,
                'image_url' => '',
                'enable_glare' => 1,
                'title_font_size' => 'medium',
                'subtitle_font_size' => 'medium',
                'description_font_size' => 'medium',
                'features_font_size' => 'medium',
                'button_font_size' => 'medium',
                'footer_font_size' => 'medium',
                'title_font_size_mobile' => 'medium',
                'subtitle_font_size_mobile' => 'medium',
                'description_font_size_mobile' => 'medium',
                'features_font_size_mobile' => 'medium',
                'button_font_size_mobile' => 'medium',
                'footer_font_size_mobile' => 'medium',
                'product_bg_color' => '#ffffff',
                'product_bg_opacity' => 20,
                'product_bg_radius' => 'none',
                'button_width' => 100,
                'button_style' => 'flat',
                'description_text_color' => '#ffffff',
                'bullet_text_color' => '#ffffff',
                'timer_bar_color' => '#ffc107',
            );
            
            // Check if settings exist for this category
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $popup_table WHERE category_id = %d",
                $category_id
            ));
            
            if ($exists) {
                // Update existing settings with defaults
                $wpdb->update(
                    $popup_table,
                    $default_settings,
                    array('category_id' => $category_id),
                    array(
                        '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
                    ),
                    array('%d')
                );
            } else {
                // Insert new settings with defaults
                $wpdb->insert(
                    $popup_table,
                    $default_settings,
                    array(
                        '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                        '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s'
                    )
                );
            }
            
            // Refresh popup settings
            $popup_settings = $default_settings;
            
            // Show success message
            echo '<div class="notice notice-success is-dismissible"><p>Popup settings reset to defaults successfully.</p></div>';
        }
        
        // Render the page
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=category-404-redirector" class="nav-tab">Category Mapping</a>
                <a href="?page=category-404-redirector-popups" class="nav-tab nav-tab-active">Popup Settings</a>
            </h2>
            
            <div class="popup-settings-container">
                <div class="popup-settings-form">
                    <h3>Select Category</h3>
                    <form method="get">
                        <input type="hidden" name="page" value="category-404-redirector-popups">
                        <select name="category_id" id="category_id">
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($selected_category_id, $category->term_id); ?>><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php submit_button('Select', 'secondary', 'submit', false); ?>
                    </form>
                    
                    <form method="post" action="" id="category-404-redirector-popup-form">
                        <?php wp_nonce_field('category_404_redirector_save_popup', 'category_404_redirector_popup_nonce'); ?>
                        <input type="hidden" name="category_id" value="<?php echo esc_attr($selected_category_id); ?>">
                        
                        <h3>Popup Settings for: <?php echo esc_html(get_cat_name($selected_category_id)); ?></h3>
                        
                        <div style="margin-bottom: 20px;">
                            <input type="submit" name="reset_defaults" class="button" value="Reset to Default Settings" onclick="return confirm('Are you sure you want to reset all settings to default values?');">
                            <p class="description">Click this button to reset all settings to their default values.</p>
                        </div>
                        
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Enable Popup</th>
                                    <td>
                                        <label for="popup_enabled">
                                            <input type="checkbox" name="popup_enabled" id="popup_enabled" <?php checked($popup_settings['enabled']); ?>>
                                            Enable popup for this category
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Content Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_title">Title</label></th>
                                    <td><input type="text" name="popup_title" id="popup_title" class="regular-text" value="<?php echo esc_attr($popup_settings['title']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_subtitle">Subtitle</label></th>
                                    <td><input type="text" name="popup_subtitle" id="popup_subtitle" class="regular-text" value="<?php echo esc_attr($popup_settings['subtitle']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_description">Description</label></th>
                                    <td><textarea name="popup_description" id="popup_description" class="large-text" rows="3"><?php echo esc_textarea($popup_settings['description']); ?></textarea></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_feature_1">Feature 1</label></th>
                                    <td><input type="text" name="popup_feature_1" id="popup_feature_1" class="regular-text" value="<?php echo esc_attr($popup_settings['feature_1']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_feature_2">Feature 2</label></th>
                                    <td><input type="text" name="popup_feature_2" id="popup_feature_2" class="regular-text" value="<?php echo esc_attr($popup_settings['feature_2']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_feature_3">Feature 3</label></th>
                                    <td><input type="text" name="popup_feature_3" id="popup_feature_3" class="regular-text" value="<?php echo esc_attr($popup_settings['feature_3']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_primary_text">Primary Button Text</label></th>
                                    <td><input type="text" name="popup_button_primary_text" id="popup_button_primary_text" class="regular-text" value="<?php echo esc_attr($popup_settings['button_primary_text']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_secondary_text">Secondary Button Text</label></th>
                                    <td><input type="text" name="popup_button_secondary_text" id="popup_button_secondary_text" class="regular-text" value="<?php echo esc_attr($popup_settings['button_secondary_text']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_footer_text">Footer Text</label></th>
                                    <td><input type="text" name="popup_footer_text" id="popup_footer_text" class="regular-text" value="<?php echo esc_attr($popup_settings['footer_text']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_countdown_seconds">Countdown Seconds</label></th>
                                    <td><input type="number" name="popup_countdown_seconds" id="popup_countdown_seconds" class="small-text" value="<?php echo esc_attr($popup_settings['countdown_seconds']); ?>" min="0" max="300"> <span class="description">Set to 0 to disable countdown</span></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Color Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_background_color">Background Color</label></th>
                                    <td><input type="text" name="popup_background_color" id="popup_background_color" class="color-picker" value="<?php echo esc_attr($popup_settings['background_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_header_color">Header Color</label></th>
                                    <td><input type="text" name="popup_header_color" id="popup_header_color" class="color-picker" value="<?php echo esc_attr($popup_settings['header_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_subtitle_background_color">Subtitle Background Color</label></th>
                                    <td><input type="text" name="popup_subtitle_background_color" id="popup_subtitle_background_color" class="color-picker" value="<?php echo esc_attr($popup_settings['subtitle_background_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_primary_color">Primary Button Color</label></th>
                                    <td><input type="text" name="popup_button_primary_color" id="popup_button_primary_color" class="color-picker" value="<?php echo esc_attr($popup_settings['button_primary_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_primary_text_color">Primary Button Text Color</label></th>
                                <td><input type="text" name="popup_button_primary_text_color" id="popup_button_primary_text_color" class="color-picker" value="<?php echo esc_attr($popup_settings['button_primary_text_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_content_background_color">Content Background Color</label></th>
                                    <td><input type="text" name="popup_content_background_color" id="popup_content_background_color" class="color-picker" value="<?php echo esc_attr($popup_settings['content_background_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_description_text_color">Description Text Color</label></th>
                                    <td><input type="text" name="popup_description_text_color" id="popup_description_text_color" class="color-picker" value="<?php echo esc_attr($popup_settings['description_text_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_bullet_text_color">Bullet Points Text Color</label></th>
                                    <td><input type="text" name="popup_bullet_text_color" id="popup_bullet_text_color" class="color-picker" value="<?php echo esc_attr($popup_settings['bullet_text_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_timer_bar_color">Timer Bar Color</label></th>
                                    <td><input type="text" name="popup_timer_bar_color" id="popup_timer_bar_color" class="color-picker" value="<?php echo esc_attr($popup_settings['timer_bar_color'] ?? '#ffc107'); ?>"></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Image Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_image_url">Product Image</label></th>
                                    <td>
                                        <input type="text" name="popup_image_url" id="popup_image_url" class="regular-text" value="<?php echo esc_url($popup_settings['image_url']); ?>">
                                        <button type="button" class="button" id="upload_image_button">Upload Image</button>
                                        <div id="image_preview">
                                            <?php if (!empty($popup_settings['image_url'])) : ?>
                                                <img src="<?php echo esc_url($popup_settings['image_url']); ?>" style="max-width: 200px; height: auto; margin-top: 10px;">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_product_bg_color">Product Background Color</label></th>
                                    <td><input type="text" name="popup_product_bg_color" id="popup_product_bg_color" class="color-picker" value="<?php echo esc_attr($popup_settings['product_bg_color']); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_product_bg_opacity">Product Background Opacity</label></th>
                                <td><input type="number" name="popup_product_bg_opacity" id="popup_product_bg_opacity" class="small-text" value="<?php echo esc_attr($popup_settings['product_bg_opacity']); ?>" min="0" max="100">%</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_product_bg_radius">Product Background Radius</label></th>
                                    <td>
                                        <select name="popup_product_bg_radius" id="popup_product_bg_radius">
                                            <?php foreach ($bg_radius_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['product_bg_radius'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Button Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_button_width">Button Width</label></th>
                                    <td><input type="number" name="popup_button_width" id="popup_button_width" class="small-text" value="<?php echo esc_attr($popup_settings['button_width']); ?>" min="20" max="100">%</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_style">Button Style</label></th>
                                    <td>
                                        <select name="popup_button_style" id="popup_button_style">
                                            <?php foreach ($button_style_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['button_style'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Button Glare Effect</th>
                                    <td>
                                        <label for="popup_enable_glare">
                                            <input type="checkbox" name="popup_enable_glare" id="popup_enable_glare" <?php checked($popup_settings['enable_glare']); ?>>
                                            Enable glare effect on button
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Font Size Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_title_font_size">Title Font Size</label></th>
                                    <td>
                                        <select name="popup_title_font_size" id="popup_title_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['title_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_subtitle_font_size">Subtitle Font Size</label></th>
                                    <td>
                                        <select name="popup_subtitle_font_size" id="popup_subtitle_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['subtitle_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_description_font_size">Description Font Size</label></th>
                                    <td>
                                        <select name="popup_description_font_size" id="popup_description_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['description_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_features_font_size">Features Font Size</label></th>
                                    <td>
                                        <select name="popup_features_font_size" id="popup_features_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['features_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_font_size">Button Font Size</label></th>
                                    <td>
                                        <select name="popup_button_font_size" id="popup_button_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['button_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_footer_font_size">Footer Font Size</label></th>
                                    <td>
                                        <select name="popup_footer_font_size" id="popup_footer_font_size">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['footer_font_size'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3>Mobile Font Size Settings</h3>
                        <table class="form-table">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="popup_title_font_size_mobile">Title Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_title_font_size_mobile" id="popup_title_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['title_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_subtitle_font_size_mobile">Subtitle Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_subtitle_font_size_mobile" id="popup_subtitle_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['subtitle_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_description_font_size_mobile">Description Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_description_font_size_mobile" id="popup_description_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['description_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_features_font_size_mobile">Features Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_features_font_size_mobile" id="popup_features_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['features_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_button_font_size_mobile">Button Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_button_font_size_mobile" id="popup_button_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['button_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="popup_footer_font_size_mobile">Footer Font Size (Mobile)</label></th>
                                    <td>
                                        <select name="popup_footer_font_size_mobile" id="popup_footer_font_size_mobile">
                                            <?php foreach ($font_size_options as $value => $label) : ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($popup_settings['footer_font_size_mobile'], $value); ?>><?php echo esc_html($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <?php submit_button('Save Popup Settings', 'primary', 'submit'); ?>
                    </form>
                    
                <div class="popup-preview-buttons">
                    <h3>Preview Popup</h3>
                    <p>Click the buttons below to preview the popup with current settings (even if not saved):</p>
                    <div class="preview-buttons">
                        <button type="button" id="check_pc_popup" class="button button-secondary">Check PC Popup</button>
                        <button type="button" id="check_mobile_popup" class="button button-secondary">Check Mobile Popup</button>
                    </div>
                </div>

                <!-- Hidden preview containers that will be used for the popup previews -->
                <div id="popup-preview-container" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 999999;">
                    <div class="popup-preview-content" style="position: relative; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box;">
                        <div id="preview-popup-content" style="position: relative; z-index: 1000000;"></div>
                        <button id="close-preview" class="button" style="position: fixed; top: 20px; right: 20px; background: #fff; color: #333; padding: 8px 15px; border-radius: 3px; cursor: pointer; z-index: 1000001; font-weight: bold;">Close Preview</button>
                    </div>
                </div>

<style>
    @keyframes pulse {
        0% { opacity: 0.8; }
        50% { opacity: 1; }
        100% { opacity: 0.8; }
    }
    .c404r-top-bar {
        animation: pulse 2s infinite;
    }
</style>
        <script type="text/javascript">
    // Direct inline script for popup preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded - setting up preview buttons');
        
        // Get the preview buttons
        var pcPreviewBtn = document.getElementById('check_pc_popup');
        var mobilePreviewBtn = document.getElementById('check_mobile_popup');
        var closePreviewBtn = document.getElementById('close-preview');
        var previewContainer = document.getElementById('popup-preview-container');
        var previewContent = document.getElementById('preview-popup-content');
        
        // Make sure the preview container is initially hidden
        if (previewContainer) {
            previewContainer.style.display = 'none';
        }
        
        if (!pcPreviewBtn || !mobilePreviewBtn) {
            console.error('Preview buttons not found!');
            return;
        }
        
        // PC Preview button click handler
        pcPreviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('PC Preview button clicked');
            showPopupPreview('desktop');
            return false;
        });
        
        // Mobile Preview button click handler
        mobilePreviewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Mobile Preview button clicked');
            showPopupPreview('mobile');
            return false;
        });
        
        // Close preview button click handler
        if (closePreviewBtn) {
            closePreviewBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Close preview button clicked');
                previewContainer.style.display = 'none';
                document.body.style.overflow = 'auto';
                return false;
            });
        }
        
        // Add event listeners to all form fields to update preview on change
        var formFields = document.querySelectorAll('#category-404-redirector-popup-form input, #category-404-redirector-popup-form textarea, #category-404-redirector-popup-form select');
        formFields.forEach(function(field) {
            field.addEventListener('change', function() {
                console.log('Form field changed:', field.id);
            });
            
            // For text inputs, also listen for keyup events
            if (field.type === 'text' || field.type === 'textarea' || field.type === 'number') {
                field.addEventListener('keyup', function() {
                    console.log('Form field keyup:', field.id);
                });
            }
        });
        
        // Function to show popup preview
        function showPopupPreview(deviceType) {
            console.log('Showing popup preview for: ' + deviceType);
            
            // Get form values - read directly from the form to ensure we have the latest values
            var title = document.getElementById('popup_title').value || "IMPORTANT NOTICE: REBRANDING UPDATE";
            var subtitle = document.getElementById('popup_subtitle').value || "This Product Is Now Rebranded Due To Counterfeit Concerns.";
            var description = document.getElementById('popup_description').value || "The new packaging comes with advanced security features to ensure authenticity, including:";
            var feature1 = document.getElementById('popup_feature_1').value || "Registered Trademark Protection";
            var feature2 = document.getElementById('popup_feature_2').value || "Embedded Security Pattern";
            var feature3 = document.getElementById('popup_feature_3').value || "Tamper-Evident Seal";
            var buttonPrimaryText = document.getElementById('popup_button_primary_text').value || "BUY NOW";
            var buttonSecondaryText = document.getElementById('popup_button_secondary_text').value || "GO BACK";
            var footerText = document.getElementById('popup_footer_text').value || "100% GUARANTEED AUTHENTIC PACKAGING";
            var countdownSeconds = document.getElementById('popup_countdown_seconds').value || "20";
            var imageUrl = document.getElementById('popup_image_url').value || "";
        
            // Get color values - for color pickers, we need to get the value directly
            var headerColor = document.getElementById('popup_header_color').value || "#e32b2b";
            var subtitleBackgroundColor = document.getElementById('popup_subtitle_background_color').value || "#ffc107";
            var buttonPrimaryColor = document.getElementById('popup_button_primary_color').value || "#ffc107";
            var buttonPrimaryTextColor = document.getElementById('popup_button_primary_text_color').value || "#000000";
            var contentBackgroundColor = document.getElementById('popup_content_background_color').value || "#000000";
            var descriptionTextColor = document.getElementById('popup_description_text_color').value || "#ffffff";
            var bulletTextColor = document.getElementById('popup_bullet_text_color').value || "#ffffff";
            var timerBarColor = document.getElementById('popup_timer_bar_color').value || "#ffc107";
            var productBgColor = document.getElementById('popup_product_bg_color').value || "#ffffff";
            
            // Get other settings
            var productBgOpacity = document.getElementById('popup_product_bg_opacity').value || "20";
            var productBgRadius = document.getElementById('popup_product_bg_radius').value || "none";
            var buttonWidth = document.getElementById('popup_button_width').value || "100";
            var buttonStyle = document.getElementById('popup_button_style').value || "flat";
            var enableGlare = document.getElementById('popup_enable_glare').checked;
        
            // Get font sizes - map to actual pixel values
            var fontSizeMap = {
                xsmall: { desktop: "16px", mobile: "14px" },
                small: { desktop: "18px", mobile: "16px" },
                medium: { desktop: "24px", mobile: "18px" },
                large: { desktop: "28px", mobile: "20px" },
                xlarge: { desktop: "32px", mobile: "22px" },
            };
        
            var descFontSizeMap = {
                xsmall: { desktop: "10px", mobile: "8px" },
                small: { desktop: "12px", mobile: "10px" },
                medium: { desktop: "16px", mobile: "12px" },
                large: { desktop: "18px", mobile: "14px" },
                xlarge: { desktop: "20px", mobile: "16px" },
            };
        
            var titleFontSizeValue,
                subtitleFontSizeValue,
                descriptionFontSizeValue,
                featuresFontSizeValue,
                buttonFontSizeValue,
                footerFontSizeValue;
        
            // Get selected font size values
            var titleFontSize = document.getElementById('popup_title_font_size').value || "medium";
            var subtitleFontSize = document.getElementById('popup_subtitle_font_size').value || "medium";
            var descriptionFontSize = document.getElementById('popup_description_font_size').value || "medium";
            var featuresFontSize = document.getElementById('popup_features_font_size').value || "medium";
            var buttonFontSize = document.getElementById('popup_button_font_size').value || "medium";
            var footerFontSize = document.getElementById('popup_footer_font_size').value || "medium";
        
            var titleFontSizeMobile = document.getElementById('popup_title_font_size_mobile').value || "medium";
            var subtitleFontSizeMobile = document.getElementById('popup_subtitle_font_size_mobile').value || "medium";
            var descriptionFontSizeMobile = document.getElementById('popup_description_font_size_mobile').value || "medium";
            var featuresFontSizeMobile = document.getElementById('popup_features_font_size_mobile').value || "medium";
            var buttonFontSizeMobile = document.getElementById('popup_button_font_size_mobile').value || "medium";
            var footerFontSizeMobile = document.getElementById('popup_footer_font_size_mobile').value || "medium";
        
            // Set actual font size values based on device type
            if (deviceType === 'mobile') {
                titleFontSizeValue = fontSizeMap[titleFontSizeMobile].mobile;
                subtitleFontSizeValue = fontSizeMap[subtitleFontSizeMobile].mobile;
                descriptionFontSizeValue = descFontSizeMap[descriptionFontSizeMobile].mobile;
                featuresFontSizeValue = descFontSizeMap[featuresFontSizeMobile].mobile;
                buttonFontSizeValue = descFontSizeMap[buttonFontSizeMobile].mobile;
                footerFontSizeValue = descFontSizeMap[footerFontSizeMobile].mobile;
            } else {
                titleFontSizeValue = fontSizeMap[titleFontSize].desktop;
                subtitleFontSizeValue = fontSizeMap[subtitleFontSize].desktop;
                descriptionFontSizeValue = descFontSizeMap[descriptionFontSize].desktop;
                featuresFontSizeValue = descFontSizeMap[featuresFontSize].desktop;
                buttonFontSizeValue = descFontSizeMap[buttonFontSize].desktop;
                footerFontSizeValue = descFontSizeMap[footerFontSize].desktop;
            }
        
            // Handle button style
            var buttonBorderRadius = "4px";
            var buttonBorder = "none";
            var buttonBoxShadow = "none";
            var buttonBackgroundImage = "none";
        
            switch (buttonStyle) {
                case "rounded":
                    buttonBorderRadius = "50px";
                    break;
                case "outlined":
                    buttonBorder = "2px solid " + buttonPrimaryColor;
                    buttonBackgroundImage = "none";
                    buttonPrimaryColor = "transparent";
                    break;
                case "gradient":
                    buttonBackgroundImage = "linear-gradient(to right, " + buttonPrimaryColor + ", #ff9800)";
                    buttonPrimaryColor = "transparent";
                    break;
                case "shadow":
                    buttonBoxShadow = "0 4px 6px rgba(0, 0, 0, 0.3)";
                    break;
                case "3d":
                    buttonBoxShadow = "0 4px 0 rgba(0, 0, 0, 0.2)";
                    break;
                case "neon":
                    buttonBoxShadow = "0 0 10px " + buttonPrimaryColor + ", 0 0 20px " + buttonPrimaryColor;
                    break;
                case "glossy":
                    buttonBackgroundImage =
                        "linear-gradient(to bottom, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 50%, rgba(0, 0, 0, 0.1) 51%, rgba(0, 0, 0, 0.1) 100%)";
                    break;
                case "shiny":
                    buttonBackgroundImage =
                        "linear-gradient(-45deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.2) 25%, rgba(255, 255, 255, 0.7) 25%, rgba(255, 255, 255, 0.7) 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, rgba(255, 255, 255, 0) 75%)";
                    break;
                case "metallic":
                    buttonBackgroundImage =
                        "linear-gradient(to bottom, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0.4) 47%, rgba(0, 0, 0, 0.05) 53%, rgba(0, 0, 0, 0.1) 100%)";
                    break;
                case "pill":
                    buttonBorderRadius = "50px";
                    buttonBoxShadow = "inset 0 -3px 0 rgba(0, 0, 0, 0.15)";
                    break;
                case "skeuomorphic":
                    buttonBorderRadius = "6px";
                    buttonBoxShadow = "inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3)";
                    buttonBorder = "none";
                    buttonBackgroundImage =
                        "linear-gradient(to bottom, " + buttonPrimaryColor + " 0%, " + shadeColor(buttonPrimaryColor, -20) + " 100%)";
                    break;
            }
        
            // Helper function to darken a color
            function shadeColor(color, percent) {
                var R = parseInt(color.substring(1, 3), 16);
                var G = parseInt(color.substring(3, 5), 16);
                var B = parseInt(color.substring(5, 7), 16);
            
                R = parseInt((R * (100 + percent)) / 100);
                G = parseInt((G * (100 + percent)) / 100);
                B = parseInt((B * (100 + percent)) / 100);
            
                R = R < 255 ? R : 255;
                G = G < 255 ? G : 255;
                B = B < 255 ? B : 255;
            
                var RR = R.toString(16).length == 1 ? "0" + R.toString(16) : R.toString(16);
                var GG = G.toString(16).length == 1 ? "0" + G.toString(16) : G.toString(16);
                var BB = B.toString(16).length == 1 ? "0" + B.toString(16) : B.toString(16);
            
                return "#" + RR + GG + BB;
            }
        
            // Create popup HTML with timer bar animation
            var popupHtml = "";
        
            // Add CSS for timer bar animation
            popupHtml += `
                <style>
                @keyframes timerBarDecrease {
                    0% { width: 100%; }
                    100% { width: 0%; }
                }
                .c404r-timer-bar {
                    position: absolute;
                    top: 0;
                    left: 0;
                    height: 8px;
                    background-color: ${timerBarColor};
                    z-index: 11;
                    width: 100%;
                    animation: timerBarDecrease ${countdownSeconds}s linear forwards;
                }
                </style>
            `;
        
            if (deviceType === 'mobile') {
                // Mobile preview with device frame
                popupHtml += `
                <div style="position: relative; width: 375px; height: 667px; margin: 0 auto; background: #1e1e1e; border-radius: 40px; padding: 10px; box-shadow: 0 0 0 10px #111, 0 20px 40px rgba(0,0,0,0.4); overflow: hidden;">
                    <!-- Phone notch -->
                    <div style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 25px; background: #1e1e1e; border-radius: 0 0 15px 15px; z-index: 10;"></div>
                    
                    <!-- Phone content area -->
                    <div style="width: 100%; height: 100%; background: white; border-radius: 30px; overflow: hidden; position: relative;">
                    <!-- Popup content -->
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); display: flex; justify-content: center; align-items: center; padding: 10px; box-sizing: border-box;">
                        <div style="position: relative; width: 100%; max-width: 280px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                        <div class="c404r-timer-bar"></div>
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 6px; background-color: ${subtitleBackgroundColor}; z-index: 10;"></div>
                        
                        <div style="position: relative; padding: 15px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; flex-direction: column; align-items: center; background-color: ${headerColor};">
                            <div style="margin-bottom: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            </div>
                            <h2 style="margin: 0; font-size: ${titleFontSizeValue}; font-weight: 700; color: white; text-align: center; text-transform: uppercase;">${title}</h2>
                            
                            <div style="position: absolute; top: 5px; right: 5px; display: flex; align-items: center;">
                            <div style="display: flex; align-items: center; margin-right: 5px;">
                                <button style="background-color: transparent; border: 1px solid white; color: white; padding: 1px 4px; border-radius: 4px; font-size: 10px; cursor: pointer; text-transform: uppercase; margin-right: 5px; height: 24px; display: flex; align-items: center; justify-content: center; width: 50px;">${buttonSecondaryText}</button>
                                <div style="background-color: #000; color: #fff; padding: 2px 6px; border-radius: 12px; font-size: 12px; display: flex; align-items: center; margin-right: 5px; height: 24px;">${countdownSeconds}s</div>
                                <button style="width: 24px; height: 24px; background-color: transparent; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; padding: 0; margin-left: 2px; border-radius: 50%; color: white;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white">
                                    <rect x="6" y="4" width="4" height="16"></rect>
                                    <rect x="14" y="4" width="4" height="16"></rect>
                                </svg>
                                </button>
                            </div>
                            <button style="width: 24px; height: 24px; background-color: transparent; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; padding: 0; margin-left: 2px; border-radius: 50%; color: white;">×</button>
                            </div>
                        </div>
                        
                        <div style="padding: 8px 15px; text-align: center; background-color: ${subtitleBackgroundColor};">
                            <p style="margin: 0; font-size: ${subtitleFontSizeValue}; font-weight: 700; color: black;">${subtitle}</p>
                        </div>
                        
                        <div style="padding: 15px; flex-grow: 1; background-color: ${contentBackgroundColor};">
                            <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 15px; align-items: center;">
                            ${
                                imageUrl
                                ? `
                            <div style="flex: 0 0 auto; width: 100px; height: 150px; display: flex; justify-content: center; align-items: center; position: relative; margin-bottom: 10px;">
                                <div style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; position: relative; overflow: hidden;">
                                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0; background-color: ${productBgColor}; opacity: ${productBgOpacity / 100}; ${productBgRadius === "small" ? "border-radius: 8px;" : productBgRadius === "medium" ? "border-radius: 16px;" : productBgRadius === "large" ? "border-radius: 50%;" : ""}"></div>
                                <img src="${imageUrl}" alt="Product Image" style="max-width: 100%; max-height: 100%; object-fit: contain; position: relative; z-index: 1;">
                                </div>
                            </div>
                            `
                                : ""
                            }
                            
                            <div style="flex: 1; display: flex; flex-direction: column; width: 100%;">
                                <p style="margin: 0 0 12px 0; font-size: ${descriptionFontSizeValue}; line-height: 1.4; color: ${descriptionTextColor};">${description}</p>
                                
                                <ul style="list-style: none; padding: 0; margin: 0 0 15px 0;">
                                <li style="display: flex; align-items: center; margin-bottom: 8px; font-size: ${featuresFontSizeValue}; line-height: 1.4; color: ${bulletTextColor};">
                                    <span style="display: inline-flex; justify-content: center; align-items: center; width: 18px; height: 18px; background-color: #4caf50; border-radius: 50%; margin-right: 8px; color: white; font-weight: bold; font-size: 10px;">✓</span>
                                    ${feature1}
                                </li>
                                <li style="display: flex; align-items: center; margin-bottom: 8px; font-size: ${featuresFontSizeValue}; line-height: 1.4; color: ${bulletTextColor};">
                                    <span style="display: inline-flex; justify-content: center; align-items: center; width: 18px; height: 18px; background-color: #4caf50; border-radius: 50%; margin-right: 8px; color: white; font-weight: bold; font-size: 10px;">✓</span>
                                    ${feature2}
                                </li>
                                <li style="display: flex; align-items: center; margin-bottom: 8px; font-size: ${featuresFontSizeValue}; line-height: 1.4; color: ${bulletTextColor};">
                                    <span style="display: inline-flex; justify-content: center; align-items: center; width: 18px; height: 18px; background-color: #4caf50; border-radius: 50%; margin-right: 8px; color: white; font-weight: bold; font-size: 10px;">✓</span>
                                    ${feature3}
                                </li>
                                </ul>
                                
                                <button style="display: block; width: ${buttonWidth}%; padding: 10px 15px; border: ${buttonBorder}; border-radius: ${buttonBorderRadius}; font-size: ${buttonFontSizeValue}; font-weight: 700; cursor: pointer; text-transform: uppercase; background-color: ${buttonPrimaryColor}; background-image: ${buttonBackgroundImage}; box-shadow: ${buttonBoxShadow}; color: ${buttonPrimaryTextColor}; position: relative; overflow: hidden; text-align: center;">
                                ${enableGlare ? '<div style="position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.4) 50%, rgba(255, 255, 255, 0) 100%); z-index: 1; pointer-events: none; animation: glare 2s infinite;"></div>' : ""}
                                ${buttonPrimaryText}
                                </button>
                            </div>
                            </div>
                        </div>
                        
                        <div style="padding: 10px; text-align: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; background-color: ${headerColor};">
                            <p style="margin: 0; font-size: ${footerFontSizeValue}; font-weight: 700; color: white; text-transform: uppercase;">${footerText}</p>
                        </div>
                        </div>
                    </div>
                    </div>
                    
                    <!-- Home indicator -->
                    <div style="position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); width: 120px; height: 5px; background: #333; border-radius: 3px;"></div>
                </div>
                
                <!-- Mobile label -->
                <div style="position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px; font-weight: bold;">
                    Mobile Preview
                </div>
                `;
            } else {
                // Desktop preview
                popupHtml += `
                <div style="position: relative; width: 100%; max-width: 800px; max-height: 90vh; overflow-y: auto; border-radius: 8px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); display: flex; flex-direction: column; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
                    <div class="c404r-timer-bar"></div>
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 8px; background-color: ${subtitleBackgroundColor}; z-index: 10;"></div>
                    
                    <div style="position: relative; padding: 20px; text-align: center; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; flex-direction: column; align-items: center; background-color: ${headerColor};">
                    <div style="margin-bottom: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h2 style="margin: 0; font-size: ${titleFontSizeValue}; font-weight: 700; color: white; text-align: center; text-transform: uppercase;">${title}</h2>
                    
                    <div style="position: absolute; top: 10px; right: 10px; display: flex; align-items: center;">
                        <div style="display: flex; align-items: center; margin-right: 10px;">
                        <button style="background-color: transparent; border: 1px solid white; color: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; cursor: pointer; text-transform: uppercase; margin-right: 5px; height: 32px; display: flex; align-items: center; justify-content: center; width: 70px;">${buttonSecondaryText}</button>
                        <div style="background-color: #000; color: #fff; padding: 2px 8px; border-radius: 15px; font-size: 16px; display: flex; align-items: center; margin-right: 5px; height: 32px;">${countdownSeconds}s</div>
                        <button style="width: 32px; height: 32px; background-color: transparent; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; padding: 0; margin-left: 5px; border-radius: 50%; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="white">
                          <rect x="6" y="4" width="4" height="16"></rect>
                          <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                      </button>
                    </div>
                    <button style="width: 32px; height: 32px; background-color: transparent; border: none; font-size: 24px; color: white; cursor: pointer; display: flex; justify-content: center; align-items: center; padding: 0; border-radius: 50%;">×</button>
                  </div>
                </div>
                
                <div style="padding: 12px 20px; text-align: center; background-color: ${subtitleBackgroundColor};">
                  <p style="margin: 0; font-size: ${subtitleFontSizeValue}; font-weight: 700; color: black;">${subtitle}</p>
                </div>
                
                <div style="padding: 20px; flex-grow: 1; background-color: ${contentBackgroundColor};">
                  <div style="display: flex; flex-direction: row; gap: 20px; margin-bottom: 20px; align-items: flex-start;">
                    ${
                      imageUrl
                        ? `
                    <div style="flex: 0 0 auto; width: 200px; height: 300px; display: flex; justify-content: center; align-items: center; position: relative;">
                      <div style="width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0; background-color: ${productBgColor}; opacity: ${productBgOpacity / 100}; ${productBgRadius === "small" ? "border-radius: 8px;" : productBgRadius === "medium" ? "border-radius: 16px;" : productBgRadius === "large" ? "border-radius: 50%;" : ""}"></div>
                        <img src="${imageUrl}" alt="Product Image" style="max-width: 100%; max-height: 100%; object-fit: contain; position: relative; z-index: 1;">
                      </div>
                    </div>
                    `
                        : ""
                    }
                    
                    <div style="flex: 1; display: flex; flex-direction: column;">
                      <p style="margin: 0 0 15px 0; font-size: ${descriptionFontSizeValue}; line-height: 1.5; color: ${descriptionTextColor};">${description}</p>
                      
                      <ul style="list-style: none; padding: 0; margin: 0 0 20px 0;">
                        <li style="display: flex; align-items: center; margin-bottom: 10px; font-size: ${featuresFontSizeValue}; line-height: 1.5; color: ${bulletTextColor};">
                          <span style="display: inline-flex; justify-content: center; align-items: center; width: 24px; height: 24px; background-color: #4caf50; border-radius: 50%; margin-right: 10px; color: white; font-weight: bold;">✓</span>
                          ${feature1}
                        </li>
                        <li style="display: flex; align-items: center; margin-bottom: 10px; font-size: ${featuresFontSizeValue}; line-height: 1.5; color: ${bulletTextColor};">
                          <span style="display: inline-flex; justify-content: center; align-items: center; width: 24px; height: 24px; background-color: #4caf50; border-radius: 50%; margin-right: 10px; color: white; font-weight: bold;">✓</span>
                          ${feature2}
                        </li>
                        <li style="display: flex; align-items: center; margin-bottom: 10px; font-size: ${featuresFontSizeValue}; line-height: 1.5; color: ${bulletTextColor};">
                          <span style="display: inline-flex; justify-content: center; align-items: center; width: 24px; height: 24px; background-color: #4caf50; border-radius: 50%; margin-right: 10px; color: white; font-weight: bold;">✓</span>
                          ${feature3}
                        </li>
                      </ul>
                      
                      <button style="display: block; width: ${buttonWidth}%; padding: 12px 20px; border: ${buttonBorder}; border-radius: ${buttonBorderRadius}; font-size: ${buttonFontSizeValue}; font-weight: 700; cursor: pointer; text-transform: uppercase; background-color: ${buttonPrimaryColor}; background-image: ${buttonBackgroundImage}; box-shadow: ${buttonBoxShadow}; color: ${buttonPrimaryTextColor}; position: relative; overflow: hidden; text-align: center;">
                        ${enableGlare ? '<div style="position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.4) 50%, rgba(255, 255, 255, 0) 100%); z-index: 1; pointer-events: none; animation: glare 2s infinite;" class="c404r-btn-glare"></div>' : ""}
                        ${buttonPrimaryText}
                      </button>
                    </div>
                  </div>
                </div>
                
                <div style="padding: 15px; text-align: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; background-color: ${headerColor};">
                  <p style="margin: 0; font-size: ${footerFontSizeValue}; font-weight: 700; color: white; text-transform: uppercase;">${footerText}</p>
                </div>
              </div>
            `;
            }
        
            // Add glare animation if enabled
            if (enableGlare) {
                popupHtml =
                `
                <style>
                    @keyframes glare {
                    0% { left: -100%; }
                    100% { left: 100%; }
                    }
                </style>
                ` + popupHtml;
            }
        
            // Show the preview container
            previewContainer.style.display = 'flex';
            previewContainer.style.position = 'fixed';
            previewContainer.style.top = '0';
            previewContainer.style.left = '0';
            previewContainer.style.width = '100%';
            previewContainer.style.height = '100%';
            previewContainer.style.backgroundColor = 'rgba(0,0,0,0.9)';
            previewContainer.style.zIndex = '999999';
            previewContainer.style.justifyContent = 'center';
            previewContainer.style.alignItems = 'center';
            previewContainer.style.padding = '20px';
            previewContainer.style.boxSizing = 'border-box';
            
            document.body.style.overflow = 'hidden'; // Prevent scrolling
            
            // Set the preview content
            if (previewContent) {
                previewContent.innerHTML = popupHtml;
                
                // Add close button
                var closeBtn = document.createElement('button');
                closeBtn.id = 'preview-close-btn';
                closeBtn.textContent = 'Close Preview';
                closeBtn.style.position = 'fixed';
                closeBtn.style.top = '20px';
                closeBtn.style.right = '20px';
                closeBtn.style.background = '#fff';
                closeBtn.style.color = '#333';
                closeBtn.style.padding = '8px 15px';
                closeBtn.style.borderRadius = '3px';
                closeBtn.style.cursor = 'pointer';
                closeBtn.style.zIndex = '1000001';
                closeBtn.style.fontWeight = 'bold';
                closeBtn.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.2)';
                closeBtn.style.border = 'none';
                
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    previewContainer.style.display = 'none';
                    document.body.style.overflow = 'auto';
                });
                
                previewContent.appendChild(closeBtn);
            }
        }
    });
</script>
            </div>
        </div>
        <?php
    }
}
