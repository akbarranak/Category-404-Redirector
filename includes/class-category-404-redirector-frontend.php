<?php
/**
 * The frontend functionality of the plugin.
 *
 * @link       https://www.facebook.com/geekishrahul
 * @since      0.3
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 */

/**
 * The frontend functionality of the plugin.
 *
 * Defines the plugin frontend functionality.
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 * @author     Rahul Rauniyar
 */
class Category_404_Redirector_Frontend {

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.3
     */
    public function __construct() {
    }

    /**
     * Register all of the hooks related to the frontend functionality.
     *
     * @since    0.3
     */
    public function init() {
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
        
        // Register AJAX handlers
        add_action('wp_ajax_category_404_redirector_check_url', array($this, 'check_url'));
        add_action('wp_ajax_nopriv_category_404_redirector_check_url', array($this, 'check_url'));
    }

    /**
     * Register the JavaScript and CSS for the frontend.
     *
     * @since    0.3
     */
    public function enqueue_scripts() {
        // Only enqueue on single posts
        if (!is_single()) {
            return;
        }
        
        // Get the current post's categories
        $post_id = get_the_ID();
        $categories = get_the_category($post_id);
        
        if (empty($categories)) {
            return;
        }
        
        // Get the first category ID
        $category_id = $categories[0]->term_id;
        
        // Get the default link for this category
        global $wpdb;
        $table_name = $wpdb->prefix . 'category_404_redirector';
        $default_link = $wpdb->get_var($wpdb->prepare(
            "SELECT default_link FROM $table_name WHERE category_id = %d",
            $category_id
        ));
        
        // If no default link is set, use the home URL
        if (empty($default_link)) {
            $default_link = home_url();
        }
        
        // Get popup settings
        $popup_table = $wpdb->prefix . 'category_404_redirector_popups';
        $popup_settings = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $popup_table WHERE category_id = %d",
            $category_id
        ), ARRAY_A);
        
        // Check if popup is enabled
        $popup_enabled = false;
        if ($popup_settings && isset($popup_settings['enabled']) && $popup_settings['enabled']) {
            $popup_enabled = true;
            
            // Enqueue popup CSS
            wp_enqueue_style(
                'category-404-redirector-popup-style',
                CATEGORY_404_REDIRECTOR_URL . 'css/popup.css',
                array(),
                CATEGORY_404_REDIRECTOR_VERSION
            );
        }
        
        // Get debug settings
        $debug_mode = get_option('category_404_redirector_debug_mode', false);
        $force_popup = get_option('category_404_redirector_force_popup', false);
        
        // Enqueue the script with higher priority and cache busting
        wp_enqueue_script(
            'category-404-redirector-script',
            CATEGORY_404_REDIRECTOR_URL . 'js/link-handler.js',
            array('jquery'),
            CATEGORY_404_REDIRECTOR_VERSION . '-' . time(), // Add timestamp for cache busting
            true
        );
        
        // Prepare popup data for JavaScript
        $popup_data = array();
        if ($popup_settings) {
            $popup_data = array(
                'title' => $popup_settings['title'],
                'subtitle' => $popup_settings['subtitle'],
                'description' => $popup_settings['description'],
                'feature1' => $popup_settings['feature_1'],
                'feature2' => $popup_settings['feature_2'],
                'feature3' => $popup_settings['feature_3'],
                'buttonPrimaryText' => $popup_settings['button_primary_text'],
                'buttonSecondaryText' => $popup_settings['button_secondary_text'],
                'footerText' => $popup_settings['footer_text'],
                'backgroundColor' => $popup_settings['background_color'],
                'headerColor' => $popup_settings['header_color'],
                'subtitleBackgroundColor' => $popup_settings['subtitle_background_color'],
                'buttonPrimaryColor' => $popup_settings['button_primary_color'],
                'buttonPrimaryTextColor' => $popup_settings['button_primary_text_color'],
                'buttonSecondaryColor' => $popup_settings['button_secondary_color'],
                'buttonSecondaryTextColor' => $popup_settings['button_secondary_text_color'],
                'contentBackgroundColor' => $popup_settings['content_background_color'],
                'textColor' => $popup_settings['text_color'],
                'countdownSeconds' => intval($popup_settings['countdown_seconds']),
                'imageUrl' => $popup_settings['image_url'],
                'enableGlare' => (bool) $popup_settings['enable_glare'],
                'titleFontSize' => $popup_settings['title_font_size'],
                'subtitleFontSize' => $popup_settings['subtitle_font_size'],
                'descriptionFontSize' => $popup_settings['description_font_size'],
                'featuresFontSize' => $popup_settings['features_font_size'],
                'buttonFontSize' => $popup_settings['button_font_size'],
                'footerFontSize' => $popup_settings['footer_font_size'],
                'titleFontSizeMobile' => $popup_settings['title_font_size_mobile'],
                'subtitleFontSizeMobile' => $popup_settings['subtitle_font_size_mobile'],
                'descriptionFontSizeMobile' => $popup_settings['description_font_size_mobile'],
                'featuresFontSizeMobile' => $popup_settings['features_font_size_mobile'],
                'buttonFontSizeMobile' => $popup_settings['button_font_size_mobile'],
                'footerFontSizeMobile' => $popup_settings['footer_font_size_mobile'],
                'productBgColor' => $popup_settings['product_bg_color'],
                'productBgOpacity' => intval($popup_settings['product_bg_opacity']),
                'productBgRadius' => $popup_settings['product_bg_radius'],
                'buttonWidth' => intval($popup_settings['button_width']),
                'buttonStyle' => $popup_settings['button_style'],
                'descriptionTextColor' => $popup_settings['description_text_color'],
                'bulletTextColor' => $popup_settings['bullet_text_color'],
            );
        }
        
        // Pass data to the script
        wp_localize_script(
            'category-404-redirector-script',
            'category404RedirectorData',
            array(
                'siteUrl' => esc_url(site_url()),
                'defaultLink' => esc_url($default_link),
                'ajaxUrl' => esc_url(admin_url('admin-ajax.php')),
                'nonce' => wp_create_nonce('category-404-redirector-nonce'),
                'popupEnabled' => $popup_enabled,
                'forcePopup' => (bool) $force_popup,
                'debugMode' => (bool) $debug_mode,
                'popup' => $popup_data,
            )
        );
    }

    /**
     * AJAX handler to check if a URL exists.
     *
     * @since    0.3
     */
    public function check_url() {
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
        
        // Check if the URL is internal
        $site_url = site_url();
        $is_internal = (strpos($url, $site_url) === 0);
        
        if ($is_internal) {
            // For internal URLs, check if the page exists
            $path = str_replace($site_url, '', $url);
            $path = ltrim($path, '/');
            
            // Check if it's a valid WordPress page/post
            $page = get_page_by_path($path, OBJECT, array('post', 'page'));
            
            if ($page) {
                wp_send_json_success(array('exists' => true));
            } else {
                // Check if it's a category or tag
                $term = term_exists(basename($path), array('category', 'post_tag'));
                
                if ($term) {
                    wp_send_json_success(array('exists' => true));
                } else {
                    wp_send_json_success(array('exists' => false));
                }
            }
        } else {
            // For external URLs, assume they exist
            wp_send_json_success(array('exists' => true));
        }
        
        exit;
    }
}
