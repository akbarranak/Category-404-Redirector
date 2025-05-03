<?php
/**
 * The settings functionality of the plugin.
 *
 * @link       https://www.facebook.com/geekishrahul
 * @since      0.3
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 */

/**
 * The settings functionality of the plugin.
 *
 * Defines the plugin settings page and functionality.
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 * @author     Rahul Rauniyar
 */
class Category_404_Redirector_Settings {

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.3
     */
    public function __construct() {
    }

    /**
     * Register all of the hooks related to the settings functionality.
     *
     * @since    0.3
     */
    public function init() {
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submissions'));
        
        // Register debug settings
        add_action('admin_init', array($this, 'register_debug_settings'));
    }
    
    /**
     * Register debug settings.
     *
     * @since    0.3
     */
    public function register_debug_settings() {
        register_setting('category_404_redirector_settings', 'category_404_redirector_debug_mode');
        register_setting('category_404_redirector_settings', 'category_404_redirector_force_popup');
    }

    /**
     * Handle form submissions.
     *
     * @since    0.3
     */
    public function handle_form_submissions() {
        // Only run on our settings page
        if (!isset($_GET['page']) || $_GET['page'] !== 'category-404-redirector') {
            return;
        }
    }
}
