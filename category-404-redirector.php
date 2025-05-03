<?php
/**
* Plugin Name: Category 404 Redirector
* Plugin URI: https://www.facebook.com/geekishrahul
* Description: Redirects non-existent links, hash links, and javascript:void(0) links in posts to a default link based on the post's category. Now with customizable popups!
* Version: 0.3
* Author: Rahul Rauniyar
* Author URI: https://www.facebook.com/geekishrahul
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: category-404-redirector
* Domain Path: /languages
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
   die;
}

// Define plugin constants
define('CATEGORY_404_REDIRECTOR_VERSION', '0.3');
define('CATEGORY_404_REDIRECTOR_PATH', plugin_dir_path(__FILE__));
define('CATEGORY_404_REDIRECTOR_URL', plugin_dir_url(__FILE__));
define('CATEGORY_404_REDIRECTOR_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-activator.php';
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-deactivator.php';
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-admin.php';
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-settings.php';
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-popups.php';
require_once CATEGORY_404_REDIRECTOR_PATH . 'includes/class-category-404-redirector-frontend.php';

/**
* The code that runs during plugin activation.
*/
function category_404_redirector_activate() {
   Category_404_Redirector_Activator::activate();
}
register_activation_hook(__FILE__, 'category_404_redirector_activate');

/**
* The code that runs during plugin deactivation.
*/
function category_404_redirector_deactivate() {
   Category_404_Redirector_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'category_404_redirector_deactivate');

/**
* Initialize the plugin
*/
function category_404_redirector_init() {
   // Initialize admin functionality
   $admin = new Category_404_Redirector_Admin();
   $admin->init();
   
   // Initialize settings
   $settings = new Category_404_Redirector_Settings();
   $settings->init();
   
   // Initialize popups
   $popups = new Category_404_Redirector_Popups();
   $popups->init();
   
   // Initialize frontend
   $frontend = new Category_404_Redirector_Frontend();
   $frontend->init();
}
add_action('plugins_loaded', 'category_404_redirector_init');
