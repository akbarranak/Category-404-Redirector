<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.facebook.com/geekishrahul
 * @since      0.3
 *
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.3
 * @package    Category_404_Redirector
 * @subpackage Category_404_Redirector/includes
 * @author     Rahul Rauniyar
 */
class Category_404_Redirector_Activator {

   /**
    * Activate the plugin.
    *
    * Create necessary database tables and files during plugin activation.
    *
    * @since    0.3
    */
   public static function activate() {
       self::create_tables();
       self::create_directories();
       self::create_js_files();
       self::create_css_files();
   }

   /**
    * Create database tables.
    *
    * @since    0.3
    */
   private static function create_tables() {
       global $wpdb;
       $table_name = $wpdb->prefix . 'category_404_redirector';
       
       $charset_collate = $wpdb->get_charset_collate();
       
       $sql = "CREATE TABLE $table_name (
           id mediumint(9) NOT NULL AUTO_INCREMENT,
           category_id bigint(20) NOT NULL,
           default_link text NOT NULL,
           PRIMARY KEY  (id),
           UNIQUE KEY category_id (category_id)
       ) $charset_collate;";
       
       require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
       dbDelta($sql);
       
       // Create popup settings table
       $popup_table = $wpdb->prefix . 'category_404_redirector_popups';
       
       $sql = "CREATE TABLE $popup_table (
           id mediumint(9) NOT NULL AUTO_INCREMENT,
           category_id bigint(20) NOT NULL,
           enabled tinyint(1) DEFAULT 0 NOT NULL,
           title text NOT NULL,
           subtitle text NOT NULL,
           description text NOT NULL,
           feature_1 text NOT NULL,
           feature_2 text NOT NULL,
           feature_3 text NOT NULL,
           button_primary_text varchar(50) NOT NULL,
           button_secondary_text varchar(50) NOT NULL,
           footer_text text NOT NULL,
           background_color varchar(20) NOT NULL,
           header_color varchar(20) NOT NULL,
           subtitle_background_color varchar(20) NOT NULL,
           button_primary_color varchar(20) NOT NULL,
           button_primary_text_color varchar(20) NOT NULL,
           button_secondary_color varchar(20) NOT NULL,
           button_secondary_text_color varchar(20) NOT NULL,
           content_background_color varchar(20) NOT NULL,
           text_color varchar(20) NOT NULL,
           countdown_seconds int(3) DEFAULT 20 NOT NULL,
           image_url text DEFAULT '' NOT NULL,
           enable_glare tinyint(1) DEFAULT 1 NOT NULL,
           title_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           subtitle_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           description_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           features_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           button_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           footer_font_size varchar(20) DEFAULT 'medium' NOT NULL,
           title_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           subtitle_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           description_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           features_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           button_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           footer_font_size_mobile varchar(20) DEFAULT 'medium' NOT NULL,
           product_bg_color varchar(20) DEFAULT '#ffffff' NOT NULL,
           product_bg_opacity int(3) DEFAULT 20 NOT NULL,
           product_bg_radius varchar(20) DEFAULT 'none' NOT NULL,
           button_width int(3) DEFAULT 100 NOT NULL,
           button_style varchar(20) DEFAULT 'flat' NOT NULL,
           description_text_color varchar(20) DEFAULT '#ffffff' NOT NULL,
           bullet_text_color varchar(20) DEFAULT '#ffffff' NOT NULL,
           timer_bar_color varchar(20) DEFAULT '#ffc107' NOT NULL,
           PRIMARY KEY  (id),
           UNIQUE KEY category_id (category_id)
       ) $charset_collate;";
       
       dbDelta($sql);
   }

   /**
    * Create necessary directories.
    *
    * @since    0.3
    */
   private static function create_directories() {
       // Create the js directory if it doesn't exist
       $js_dir = CATEGORY_404_REDIRECTOR_PATH . 'js';
       if (!file_exists($js_dir)) {
           mkdir($js_dir, 0755);
       }
       
       // Create the css directory if it doesn't exist
       $css_dir = CATEGORY_404_REDIRECTOR_PATH . 'css';
       if (!file_exists($css_dir)) {
           mkdir($css_dir, 0755);
       }
       
       // Create the js-templates directory if it doesn't exist
       $js_templates_dir = CATEGORY_404_REDIRECTOR_PATH . 'js-templates';
       if (!file_exists($js_templates_dir)) {
           mkdir($js_templates_dir, 0755);
       }
       
       // Create the css-templates directory if it doesn't exist
       $css_templates_dir = CATEGORY_404_REDIRECTOR_PATH . 'css-templates';
       if (!file_exists($css_templates_dir)) {
           mkdir($css_templates_dir, 0755);
       }
   }

   /**
    * Create JS files.
    *
    * @since    0.3
    */
   private static function create_js_files() {
       // Create the js directory if it doesn't exist
       $js_dir = CATEGORY_404_REDIRECTOR_PATH . 'js';
       if (!file_exists($js_dir)) {
           mkdir($js_dir, 0755);
       }
       
       // Create the js-templates directory if it doesn't exist
       $js_templates_dir = CATEGORY_404_REDIRECTOR_PATH . 'js-templates';
       if (!file_exists($js_templates_dir)) {
           mkdir($js_templates_dir, 0755);
       }
       
       // Create the link-handler.js template file
       $link_handler_template = $js_templates_dir . '/link-handler.js';
       if (!file_exists($link_handler_template)) {
           $link_handler_content = self::get_link_handler_js_content();
           file_put_contents($link_handler_template, $link_handler_content);
       }
       
       // Create the admin.js template file
       $admin_template = $js_templates_dir . '/admin.js';
       if (!file_exists($admin_template)) {
           $admin_content = self::get_admin_js_content();
           file_put_contents($admin_template, $admin_content);
       }
       
       // Create the link-handler.js file
       $js_file = $js_dir . '/link-handler.js';
       if (!file_exists($js_file) && file_exists($link_handler_template)) {
           copy($link_handler_template, $js_file);
       } else if (!file_exists($js_file)) {
           file_put_contents($js_file, self::get_link_handler_js_content());
       }
       
       // Create the admin.js file
       $admin_js_file = $js_dir . '/admin.js';
       if (!file_exists($admin_js_file) && file_exists($admin_template)) {
           copy($admin_template, $admin_js_file);
       } else if (!file_exists($admin_js_file)) {
           file_put_contents($admin_js_file, self::get_admin_js_content());
       }
   }

   /**
    * Create CSS files.
    *
    * @since    0.3
    */
   private static function create_css_files() {
       // Create the css directory if it doesn't exist
       $css_dir = CATEGORY_404_REDIRECTOR_PATH . 'css';
       if (!file_exists($css_dir)) {
           mkdir($css_dir, 0755);
       }
       
       // Create the css-templates directory if it doesn't exist
       $css_templates_dir = CATEGORY_404_REDIRECTOR_PATH . 'css-templates';
       if (!file_exists($css_templates_dir)) {
           mkdir($css_templates_dir, 0755);
       }
       
       // Create the popup.css template file
       $popup_template = $css_templates_dir . '/popup.css';
       if (!file_exists($popup_template)) {
           $popup_content = self::get_popup_css_content();
           file_put_contents($popup_template, $popup_content);
       }
       
       // Create the admin.css template file
       $admin_template = $css_templates_dir . '/admin.css';
       if (!file_exists($admin_template)) {
           $admin_content = self::get_admin_css_content();
           file_put_contents($admin_template, $admin_content);
       }
       
       // Create the popup.css file
       $css_file = $css_dir . '/popup.css';
       if (!file_exists($css_file) && file_exists($popup_template)) {
           copy($popup_template, $css_file);
       } else if (!file_exists($css_file)) {
           file_put_contents($css_file, self::get_popup_css_content());
       }
       
       // Create the admin.css file
       $admin_css_file = $css_dir . '/admin.css';
       if (!file_exists($admin_css_file) && file_exists($admin_template)) {
           copy($admin_template, $admin_css_file);
       } else if (!file_exists($admin_css_file)) {
           file_put_contents($admin_css_file, self::get_admin_css_content());
       }
   }

   /**
    * Get link handler JS content.
    *
    * @since    0.3
    * @return   string    The content for the link-handler.js file.
    */
   private static function get_link_handler_js_content() {
       return 'jQuery(document).ready(($) => {
  // Get the site URL and default link from the localized data
  var siteUrl = category404RedirectorData.siteUrl
  var defaultLink = category404RedirectorData.defaultLink
  var ajaxUrl = category404RedirectorData.ajaxUrl
  var nonce = category404RedirectorData.nonce
  var popupEnabled = category404RedirectorData.popupEnabled
  var forcePopup = category404RedirectorData.forcePopup

  console.log("Category 404 Redirector initialized with default link: " + defaultLink)
  console.log("Popup enabled: " + popupEnabled)
  console.log("Force popup: " + forcePopup)

  // WordPress system paths that should always be excluded
  var excludedPaths = [
    "/wp-admin/",
    "/wp-content/",
    "/wp-includes/",
    "/author/",
    "/category/",
    "/tag/",
    "/page/",
    "/comments/",
    "/feed/",
    "/wp-json/",
    "/wp-login.php",
    "/wp-register.php",
  ]

  // Function to check if a URL should be excluded
  function shouldExcludeUrl(url) {
    // Skip if no URL
    if (!url) return true

    // Normalize URLs for comparison
    var normalizedUrl = url.toLowerCase()
    var normalizedSiteUrl = siteUrl.toLowerCase()

    // Handle URLs starting with www
    if (normalizedUrl.startsWith("www.")) {
      normalizedUrl = "http://" + normalizedUrl
    }

    // Extract domain from site URL for comparison
    var siteDomain = normalizedSiteUrl.replace(/^https?:\/\//, "").split("/")[0]

    // Always exclude external links
    if (
      // If URL doesn\'t start with site URL
      normalizedUrl.indexOf(normalizedSiteUrl) !== 0 &&
      // And URL doesn\'t contain the site domain (for www or http cases)
      normalizedUrl.indexOf("://" + siteDomain) === -1 &&
      // And it\'s an http/https URL
      (normalizedUrl.indexOf("http") === 0 || normalizedUrl.indexOf("www.") === 0)
    ) {
      console.log("Skipping external link: " + url)
      return true
    }

    // Skip mailto, tel, etc.
    if (
      url.startsWith("mailto:") ||
      url.startsWith("tel:") ||
      url.startsWith("sms:") ||
      url.startsWith("javascript:")
    ) {
      console.log("Skipping special link: " + url)
      return true
    }

    // For WordPress system paths
    for (var i = 0; i < excludedPaths.length; i++) {
      if (url.indexOf(siteUrl + excludedPaths[i]) === 0) {
        console.log("Skipping WordPress system URL: " + url)
        return true
      }
    }

    return false
  }

  // Function to handle link click
  function handleLinkClick(e) {
    var $link = $(this)
    var href = $link.attr("href")

    // Handle empty href or no href - redirect immediately to default link
    if (!href || href === "") {
      console.log("Empty link detected - Redirecting to: " + defaultLink)
      e.preventDefault()
      e.stopPropagation()
      window.location.href = defaultLink
      return false
    }

    // Handle hash links - redirect immediately to default link
    if (
      href === "#" ||
      href === "#respond" ||
      (href.startsWith("#") && href.length > 1 && !href.startsWith("#comment"))
    ) {
      console.log("Hash link detected: " + href + " - Redirecting to: " + defaultLink)
      e.preventDefault()
      e.stopPropagation()
      window.location.href = defaultLink
      return false
    }

    // Skip comment links
    if (href.indexOf("#comment") === 0) {
      console.log("Skipping comment link: " + href)
      return true
    }

    // Handle javascript:void(0) links
    if (href.indexOf("javascript:void(0)") !== -1 || href.indexOf("javascript:;") !== -1) {
      console.log("JavaScript void link detected - Redirecting to: " + defaultLink)
      e.preventDefault()
      e.stopPropagation()
      window.location.href = defaultLink
      return false
    }

    // For relative links, prepend the site URL
    var fullUrl = href

    // Handle URLs starting with www
    if (href.toLowerCase().startsWith("www.")) {
      fullUrl = "http://" + href
      console.log("Converted www URL to: " + fullUrl)
    }
    // Handle relative URLs
    else if (href.indexOf("http") !== 0) {
      if (href.startsWith("/")) {
        fullUrl = siteUrl + href
      } else {
        fullUrl = siteUrl + "/" + href
      }
      console.log("Converted relative URL to: " + fullUrl)
    }

    // Check if URL should be excluded
    if (shouldExcludeUrl(fullUrl)) {
      return true // Let the browser handle the link normally
    }

    // Extract domain from site URL
    var siteDomain = siteUrl.replace(/^https?:\/\//, "").split("/")[0]
    var urlDomain = fullUrl.replace(/^https?:\/\//, "").split("/")[0]

    // If the URL is on the same domain but with different protocol, normalize it
    if (urlDomain === siteDomain && fullUrl.indexOf(siteUrl) !== 0) {
      console.log("URL is on same domain but different protocol, normalizing")
      var urlPath = fullUrl.replace(/^https?:\/\/[^/]+/, "")
      fullUrl = siteUrl + urlPath
    }

    // Check if the URL exists
    console.log("Checking if URL exists: " + fullUrl)
    e.preventDefault()
    e.stopPropagation()

    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: {
        action: "category_404_redirector_check_url",
        url: fullUrl,
        nonce: nonce,
      },
      dataType: "json",
      success: (response) => {
        if (response.success) {
          if (response.data.exists) {
            console.log("URL exists, proceeding to: " + fullUrl)
            window.location.href = fullUrl
          } else {
            console.log("URL does not exist, redirecting to: " + defaultLink)
            window.location.href = defaultLink
          }
        } else {
          console.log("AJAX error, proceeding to original link: " + fullUrl)
          window.location.href = fullUrl
        }
      },
      error: (xhr, status, error) => {
        console.log("AJAX request failed: " + error)
        console.log("Proceeding to original link: " + fullUrl)
        window.location.href = fullUrl
      },
    })

    return false
  }

  // Only modify hash links within post content areas
  // This is more specific to target only content areas, not navigation or widgets
  $(
    \'.entry-content a[href="#"], .post-content a[href="#"], article .content a[href="#"], .single-post .post a[href="#"]\',
  ).each(function () {
    var $link = $(this)
    console.log("Found hash link in content: " + $link.attr("href") + " - Setting data attribute")

    // Store original href for reference
    $link.attr("data-original-href", $link.attr("href"))

    // Set the href to the default link
    $link.attr("href", defaultLink)
  })

  // Modify empty links within post content areas
  $(
    \'.entry-content a[href=""], .post-content a[href=""], article .content a[href=""], .single-post .post a[href=""], .type-post .entry a[href=""]\',
  ).each(function () {
    var $link = $(this)
    console.log("Found empty link in content - Setting data attribute")

    // Store original href for reference
    $link.attr("data-original-href", $link.attr("href"))

    // Set the href to the default link
    $link.attr("href", defaultLink)
  })

  // Also modify javascript:void(0) links within post content areas
  $(
    \'.entry-content a[href="javascript:void(0)"], .post-content a[href="javascript:void(0)"], article .content a[href="javascript:void(0)"], .single-post .post a[href="javascript:void(0)"]\',
  ).each(function () {
    var $link = $(this)
    console.log("Found JavaScript void link in content - Setting data attribute")

    // Store original href for reference
    $link.attr("data-original-href", $link.attr("href"))

    // Set the href to the default link
    $link.attr("href", defaultLink)
  })

  // Add click handler ONLY to links within post content areas
  // This is more specific to target only content areas, not navigation or widgets
  $(document).on(
    "click",
    ".entry-content a, .post-content a, article .content a, .single-post .post a, .type-post .entry a",
    handleLinkClick,
  )

  console.log("Category 404 Redirector: All event handlers attached to post content only")
})';
   }

   /**
    * Get admin JS content.
    *
    * @since    0.3
    * @return   string    The content for the admin.js file.
    */
   private static function get_admin_js_content() {
       return 'jQuery(document).ready(($) => {
  console.log("Category 404 Redirector Admin JS loaded")

  // Initialize color pickers
  if ($.fn.wpColorPicker) {
    console.log("Initializing color pickers")
    $(".color-picker").wpColorPicker({
      change: (event, ui) => {
        console.log("Color changed")
      },
      clear: () => {
        console.log("Color cleared")
      },
    })
  } else {
    console.error("WordPress Color Picker not available")
  }

  // Handle image upload
  $("#upload_image_button").click((e) => {
    e.preventDefault()
    console.log("Upload image button clicked")

    let image_frame // Declare image_frame here

    // If the media frame already exists, reopen it
    if (image_frame) {
      image_frame.open()
      return
    }

    // Create a new media frame
    image_frame = wp.media({
      // wp is available globally in WordPress admin
      title: "Select or Upload Product Image",
      button: {
        text: "Use this image",
      },
      multiple: false,
    })

    // When an image is selected in the media frame...
    image_frame.on("select", () => {
      // Get media attachment details from the frame state
      var attachment = image_frame.state().get("selection").first().toJSON()
      console.log("Image selected:", attachment)

      // Set the image URL in the input field
      $("#popup_image_url").val(attachment.url)

      // Show the image preview
      $("#image_preview").html(\'<img src="\' + attachment.url + \'" style="max-width: 100%; height: auto;">\')
    })

    // Finally, open the modal
    image_frame.open()
  })

  // Update preview when form fields change
  $("input, textarea, select").on("change keyup", function () {
    console.log("Form field changed:", $(this).attr("id"))
  })
})';
   }

   /**
    * Get popup CSS content.
    *
    * @since    0.3
    * @return   string    The content for the popup.css file.
    */
   private static function get_popup_css_content() {
       return '/* Popup Overlay */
.c404r-popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 999999;
  padding: 20px;
  box-sizing: border-box;
}

/* Popup Container */
.c404r-popup {
  position: relative;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue",
    sans-serif;
}

/* Update the yellow bar at top to be thicker with shrinking animation */
.c404r-top-bar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 8px; /* Increased from 4px to 8px to make it thicker */
  background-color: #ffc107;
  z-index: 10;
  transition: width 0.1s linear; /* Smooth transition for width changes */
}

/* Header */
.c404r-header {
  position: relative;
  padding: 20px;
  text-align: center;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.c404r-icon {
  margin-bottom: 10px;
}

.c404r-icon svg {
  width: 40px;
  height: 40px;
  stroke: white;
  fill: none;
}

.c404r-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: white;
  text-align: center;
  text-transform: uppercase;
}

/* Timer and Controls */
.c404r-controls-container {
  position: absolute;
  top: 10px;
  right: 10px;
  display: flex;
  align-items: center;
}

.c404r-timer-controls {
  display: flex;
  align-items: center;
  margin-right: 10px;
}

/* Make timer, play/pause button and cross button bigger */
.c404r-countdown {
  background-color: #000;
  color: #fff;
  padding: 2px 8px;
  border-radius: 15px;
  font-size: 16px; /* Increased from 14px */
  display: flex;
  align-items: center;
  margin-right: 5px;
  height: 28px;
  display: flex;
  align-items: center;
  margin-right: 5px;
  height: 28px; /* Increased from 24px */
}

.c404r-pause-btn {
  width: 32px; /* Increased from 28px */
  height: 32px; /* Increased from 28px */
  background-color: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0;
  margin-left: 5px;
  border-radius: 50%;
  color: white;
}

.c404r-pause-btn svg {
  width: 24px; /* Increased from 20px */
  height: 24px; /* Increased from 20px */
  fill: white;
}

/* Make the Go Back button smaller with same height as timer */
.c404r-back-btn {
  background-color: transparent;
  border: 1px solid white;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  cursor: pointer;
  text-transform: uppercase;
  margin-right: 5px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 60px; /* Added fixed width for smaller button */
}

.c404r-pause-btn:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

/* Close Button */
.c404r-close-btn {
  width: 32px; /* Increased from 28px */
  height: 32px; /* Increased from 28px */
  background-color: transparent;
  border: none;
  font-size: 24px; /* Increased from 22px */
  color: white;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0;
  border-radius: 50%;
}

.c404r-close-btn:hover {
  background-color: rgba(255, 255, 255, 0.2);
}

/* Subtitle */
.c404r-subtitle {
  padding: 12px 20px;
  text-align: center;
  background-color: #ffc107;
  border-top: 4px solid #ffc107;
  border-bottom: 4px solid #ffc107;
}

.c404r-subtitle p {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: black;
}

/* Content */
.c404r-content {
  padding: 20px;
  flex-grow: 1;
  background-color: #000;
}

.c404r-content-inner {
  display: flex;
  flex-direction: row;
  gap: 20px;
  margin-bottom: 20px;
}

.c404r-product-image-container {
  flex: 0 0 auto;
  width: 200px;
  height: 300px;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}

.c404r-product-image {
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  overflow: hidden;
}

/* Product image background with border radius options */
.c404r-product-image img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  position: relative;
  z-index: 1;
}

/* Product image background */
.c404r-product-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 0;
}

/* Product image background border radius options */
.c404r-product-bg-radius-none {
  border-radius: 0;
}

.c404r-product-bg-radius-small {
  border-radius: 8px;
}

.c404r-product-bg-radius-medium {
  border-radius: 16px;
}

.c404r-product-bg-radius-large {
  border-radius: 24px;
}

.c404r-product-bg-radius-circle {
  border-radius: 50%;
}

.c404r-content-text {
  flex: 1;
}

.c404r-content p {
  margin: 0 0 15px 0;
  font-size: 16px;
  line-height: 1.5;
  color: white;
}

.c404r-features {
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
}

.c404r-features li {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  font-size: 16px;
  line-height: 1.5;
  color: white;
}

.c404r-check-icon {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  width: 24px;
  height: 24px;
  background-color: #4caf50;
  border-radius: 50%;
  margin-right: 10px;
  color: white;
  font-weight: bold;
}

/* Buy Now Button */
.c404r-primary-btn {
  display: block;
  width: 100%;
  padding: 12px 20px;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  text-transform: uppercase;
  transition: transform 0.2s, box-shadow 0.2s;
  background-color: #ffc107;
  color: #000;
  position: relative;
  overflow: hidden;
}

/* Glare effect for button */
.c404r-btn-glare {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.4) 50%,
    rgba(255, 255, 255, 0) 100%
  );
  animation: glare-animation 2s infinite;
  z-index: 1;
  pointer-events: none;
}

@keyframes glare-animation {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

.c404r-primary-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Button Style Variations */
/* Default style (flat) */
.c404r-btn-style-flat {
  /* Default styles already applied */
}

/* Rounded style */
.c404r-btn-style-rounded {
  border-radius: 50px;
}

/* Outlined style */
.c404r-btn-style-outlined {
  background-color: transparent !important;
  border: 2px solid;
}

/* Gradient style */
.c404r-btn-style-gradient {
  background-image: linear-gradient(to right, var(--primary-color), var(--secondary-color, #ff9800)) !important;
  background-color: transparent !important;
}

/* Shadow style */
.c404r-btn-style-shadow {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}

/* 3D style */
.c404r-btn-style-3d {
  box-shadow: 0 4px 0 rgba(0, 0, 0, 0.2);
  transform: translateY(-2px);
}

.c404r-btn-style-3d:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 0 rgba(0, 0, 0, 0.2);
}

.c404r-btn-style-3d:active {
  transform: translateY(0);
  box-shadow: 0 2px 0 rgba(0, 0, 0, 0.2);
}

/* Neon style */
.c404r-btn-style-neon {
  box-shadow: 0 0 10px var(--primary-color), 0 0 20px var(--primary-color);
  text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
}

/* Glossy style */
.c404r-btn-style-glossy {
  background-image: linear-gradient(
    to bottom,
    rgba(255, 255, 255, 0.3) 0%,
    rgba(255, 255, 255, 0) 50%,
    rgba(0, 0, 0, 0.1) 51%,
    rgba(0, 0, 0, 0.1) 100%
  ) !important;
  background-color: var(--primary-color) !important;
}

/* Shiny style */
.c404r-btn-style-shiny {
  background-image: linear-gradient(
    -45deg,
    rgba(255, 255, 255, 0.2) 0%,
    rgba(255, 255, 255, 0.2) 25%,
    rgba(255, 255, 255, 0.7) 25%,
    rgba(255, 255, 255, 0.7) 50%,
    rgba(255, 255, 255, 0.2) 50%,
    rgba(255, 255, 255, 0.2) 75%,
    rgba(255, 255, 255, 0) 75%
  ) !important;
  background-size: 40px 40px;
  background-color: var(--primary-color) !important;
}

/* Metallic style */
.c404r-btn-style-metallic {
  background-image: linear-gradient(
    to bottom,
    rgba(255, 255, 255, 0.6) 0%,
    rgba(255, 255, 255, 0.4) 47%,
    rgba(0, 0, 0, 0.05) 53%,
    rgba(0, 0, 0, 0.1) 100%
  ) !important;
  background-color: var(--primary-color) !important;
}

/* Pill style */
.c404r-btn-style-pill {
  border-radius: 50px;
  box-shadow: inset 0 -3px 0 rgba(0, 0, 0, 0.15);
}

/* Skeuomorphic style */
.c404r-btn-style-skeuomorphic {
  border-radius: 6px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3);
  border-bottom: 3px solid rgba(0, 0, 0, 0.2);
}

/* Footer */
.c404r-footer {
  padding: 15px;
  text-align: center;
  border-bottom-left-radius: 8px;
  border-bottom-right-radius: 8px;
}

.c404r-footer p {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: white;
  text-transform: uppercase;
}

/* Mobile Styles */
@media (max-width: 768px) {
  .c404r-popup {
    max-height: 80vh; /* Decreased height on mobile */
  }

  .c404r-content-inner {
    flex-direction: column;
    align-items: center;
  }

  .c404r-product-image-container {
    width: 120px;
    height: 200px;
    margin-bottom: 15px;
  }

  .c404r-header h2 {
    font-size: 18px; /* Smaller on mobile */
    padding-right: 40px; /* Make room for controls */
  }

  .c404r-subtitle p {
    font-size: 12px; /* Smaller on mobile */
  }

  .c404r-content p,
  .c404r-features li {
    font-size: 12px; /* Smaller on mobile */
  }

  .c404r-controls-container {
    right: 5px;
    top: 5px;
  }

  .c404r-back-btn {
    font-size: 8px;
    padding: 1px 4px;
    height: 20px;
    width: 50px;
    position: absolute;
    left: 5px;
    top: 5px;
  }

  .c404r-countdown {
    font-size: 12px;
    height: 20px;
  }

  .c404r-pause-btn,
  .c404r-close-btn {
    width: 24px;
    height: 24px;
  }

  .c404r-pause-btn svg {
    width: 16px;
    height: 16px;
  }

  .c404r-close-btn {
    font-size: 18px;
  }

  .c404r-primary-btn {
    padding: 10px 15px;
    font-size: 14px;
  }

  .c404r-footer p {
    font-size: 12px;
  }
}

/* Add more font size variations */
/* Font size variations for title */
.c404r-title-xsmall .c404r-header h2 {
  font-size: 16px;
}
.c404r-title-small .c404r-header h2 {
  font-size: 18px;
}
.c404r-title-medium .c404r-header h2 {
  font-size: 24px;
}
.c404r-title-large .c404r-header h2 {
  font-size: 28px;
}
.c404r-title-xlarge .c404r-header h2 {
  font-size: 32px;
}

/* Font size variations for subtitle */
.c404r-subtitle-xsmall .c404r-subtitle p {
  font-size: 10px;
}
.c404r-subtitle-small .c404r-subtitle p {
  font-size: 12px;
}
.c404r-subtitle-medium .c404r-subtitle p {
  font-size: 16px;
}
.c404r-subtitle-large .c404r-subtitle p {
  font-size: 18px;
}
.c404r-subtitle-xlarge .c404r-subtitle p {
  font-size: 20px;
}

/* Font size variations for description */
.c404r-description-xsmall .c404r-content p {
  font-size: 10px;
}
.c404r-description-small .c404r-content p {
  font-size: 12px;
}
.c404r-description-medium .c404r-content p {
  font-size: 16px;
}
.c404r-description-large .c404r-content p {
  font-size: 18px;
}
.c404r-description-xlarge .c404r-content p {
  font-size: 20px;
}

/* Font size variations for features */
.c404r-features-xsmall .c404r-features li {
  font-size: 10px;
}
.c404r-features-small .c404r-features li {
  font-size: 12px;
}
.c404r-features-medium .c404r-features li {
  font-size: 16px;
}
.c404r-features-large .c404r-features li {
  font-size: 18px;
}
.c404r-features-xlarge .c404r-features li {
  font-size: 20px;
}

/* Font size variations for button */
.c404r-button-xsmall .c404r-primary-btn {
  font-size: 12px;
}
.c404r-button-small .c404r-primary-btn {
  font-size: 14px;
}
.c404r-button-medium .c404r-primary-btn {
  font-size: 16px;
}
.c404r-button-large .c404r-primary-btn {
  font-size: 18px;
}
.c404r-button-xlarge .c404r-primary-btn {
  font-size: 20px;
}

/* Font size variations for footer */
.c404r-footer-xsmall .c404r-footer p {
  font-size: 10px;
}
.c404r-footer-small .c404r-footer p {
  font-size: 12px;
}
.c404r-footer-medium .c404r-footer p {
  font-size: 14px;
}
.c404r-footer-large .c404r-footer p {
  font-size: 16px;
}
.c404r-footer-xlarge .c404r-footer p {
  font-size: 18px;
}

/* Mobile-specific font size overrides */
@media (max-width: 768px) {
  .c404r-title-mobile-xsmall .c404r-header h2 {
    font-size: 14px !important;
  }
  .c404r-title-mobile-small .c404r-header h2 {
    font-size: 16px !important;
  }
  .c404r-title-mobile-medium .c404r-header h2 {
    font-size: 18px !important;
  }
  .c404r-title-mobile-large .c404r-header h2 {
    font-size: 20px !important;
  }
  .c404r-title-mobile-xlarge .c404r-header h2 {
    font-size: 22px !important;
  }

  .c404r-subtitle-mobile-xsmall .c404r-subtitle p {
    font-size: 8px !important;
  }
  .c404r-subtitle-mobile-small .c404r-subtitle p {
    font-size: 10px !important;
  }
  .c404r-subtitle-mobile-medium .c404r-subtitle p {
    font-size: 12px !important;
  }
  .c404r-subtitle-mobile-large .c404r-subtitle p {
    font-size: 14px !important;
  }
  .c404r-subtitle-mobile-xlarge .c404r-subtitle p {
    font-size: 16px !important;
  }

  .c404r-description-mobile-xsmall .c404r-content p {
    font-size: 8px !important;
  }
  .c404r-description-mobile-small .c404r-content p {
    font-size: 10px !important;
  }
  .c404r-description-mobile-medium .c404r-content p {
    font-size: 12px !important;
  }
  .c404r-description-mobile-large .c404r-content p {
    font-size: 14px !important;
  }
  .c404r-description-mobile-xlarge .c404r-content p {
    font-size: 16px !important;
  }

  .c404r-features-mobile-xsmall .c404r-features li {
    font-size: 8px !important;
  }
  .c404r-features-mobile-small .c404r-features li {
    font-size: 10px !important;
  }
  .c404r-features-mobile-medium .c404r-features li {
    font-size: 12px !important;
  }
  .c404r-features-mobile-large .c404r-features li {
    font-size: 14px !important;
  }
  .c404r-features-mobile-xlarge .c404r-features li {
    font-size: 16px !important;
  }

  .c404r-button-mobile-xsmall .c404r-primary-btn {
    font-size: 10px !important;
  }
  .c404r-button-mobile-small .c404r-primary-btn {
    font-size: 12px !important;
  }
  .c404r-button-mobile-medium .c404r-primary-btn {
    font-size: 14px !important;
  }
  .c404r-button-mobile-large .c404r-primary-btn {
    font-size: 16px !important;
  }
  .c404r-button-mobile-xlarge .c404r-primary-btn {
    font-size: 18px !important;
  }

  .c404r-footer-mobile-xsmall .c404r-footer p {
    font-size: 8px !important;
  }
  .c404r-footer-mobile-small .c404r-footer p {
    font-size: 10px !important;
  }
  .c404r-footer-mobile-medium .c404r-footer p {
    font-size: 12px !important;
  }
  .c404r-footer-mobile-large .c404r-footer p {
    font-size: 14px !important;
  }
  .c404r-footer-mobile-xlarge .c404r-footer p {
    font-size: 16px !important;
  }
}';
   }

   /**
    * Get admin CSS content.
    *
    * @since    0.3
    * @return   string    The content for the admin.css file.
    */
   private static function get_admin_css_content() {
       return '/* Admin Styles for Category 404 Redirector */

/* Main container layout */
.popup-settings-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  margin-top: 20px;
}

.popup-settings-form {
  flex: 1;
  min-width: 500px;
}

.popup-preview {
  flex: 1;
  min-width: 500px;
}

/* Preview container */
.preview-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
}

.popup-preview-device {
  flex: 1;
  min-width: 300px;
}

.preview-frame {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  background-color: #f5f5f5;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  max-height: 600px; /* Limit height to prevent overflow */
  overflow-y: auto; /* Add scrollbar if content exceeds height */
}

.preview-frame.mobile {
  max-width: 375px;
  margin: 0 auto;
}

/* Popup Preview Styles */
.preview-popup {
  transform: scale(0.8);
  transform-origin: top center;
  margin: -20px -20px 0; /* Reduced negative margin */
}

/* Admin Form Styles */
.form-table th {
  width: 200px;
}

#image_preview {
  margin-top: 10px;
  max-width: 300px;
  border: 1px solid #ddd;
  padding: 5px;
  background-color: #f9f9f9;
  border-radius: 4px;
}

/* Color picker adjustments */
.wp-picker-container {
  display: inline-block;
}

.wp-color-result {
  margin-bottom: 0 !important;
}';
   }
}
