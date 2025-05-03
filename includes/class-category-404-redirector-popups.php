<?php
/**
 * The popups functionality of the plugin.
 *
 * @link       https://www.facebook.com/geekishrahul
 * @since      0.3
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 */

/**
 * The popups functionality of the plugin.
 *
 * Defines the plugin popups page and functionality.
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 * @author     Rahul Rauniyar
 */
class Category_404_Redirector_Popups {

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.3
     */
    public function __construct() {
    }

    /**
     * Register all of the hooks related to the popups functionality.
     *
     * @since    0.3
     */
    public function init() {
        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submissions'));
    }

    /**
     * Handle form submissions.
     *
     * @since    0.3
     */
    public function handle_form_submissions() {
        // Only run on our popups page
        if (!isset($_GET['page']) || $_GET['page'] !== 'category-404-redirector-popups') {
            return;
        }
    }
}
