// This file is intentionally left empty as we're using inline JavaScript in the admin page
// to avoid any potential caching issues or conflicts with other scripts
console.log("Category 404 Redirector Admin JS loaded - using inline script instead")

// Media uploader functionality
jQuery(document).ready(($) => {
  // Handle image upload
  $("#upload_image_button").on("click", (e) => {
    e.preventDefault()
    console.log("Upload image button clicked")

    var image_frame

    // If the media frame already exists, reopen it
    if (image_frame) {
      image_frame.open()
      return
    }

    // Create a new media frame
    if (typeof wp !== "undefined" && typeof wp.media !== "undefined") {
      image_frame = wp.media({
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
        $("#image_preview").html(
          '<img src="' + attachment.url + '" style="max-width: 200px; height: auto; margin-top: 10px;">',
        )
      })

      // Finally, open the modal
      image_frame.open()
    } else {
      console.error("WordPress Media Uploader not available")
    }
  })
})

jQuery(document).ready(($) => {
  console.log("Category 404 Redirector Admin JS loaded")

  // Initialize color pickers with change event handler
  if (typeof $.fn.wpColorPicker === "function") {
    console.log("Initializing color pickers")
    $(".color-picker").each(function () {
      $(this).wpColorPicker({
        change: function (event, ui) {
          // Update the input value when color changes
          $(this).val(ui.color.toString())
          console.log("Color changed:", $(this).attr("id"), ui.color.toString())
        },
      })
    })
  } else {
    console.error("WordPress Color Picker not available")
  }

  // Add change event handlers to all form inputs
  $(
    "#category-404-redirector-popup-form input, #category-404-redirector-popup-form textarea, #category-404-redirector-popup-form select",
  ).on("change input", function () {
    console.log("Form field changed:", $(this).attr("id"), $(this).val())
  })
})

// Handle PC popup preview button click
jQuery(document).on("click", "#check_pc_popup", (e) => {
  e.preventDefault()
  console.log("Check PC Popup button clicked")

  // Show the popup preview container first
  jQuery("#popup-preview-container").css("display", "block")
  jQuery("body").css("overflow", "hidden") // Prevent scrolling

  // Create the popup preview
  createPopupPreview("desktop")
})

// Handle Mobile popup preview button click
jQuery(document).on("click", "#check_mobile_popup", (e) => {
  e.preventDefault()
  console.log("Check Mobile Popup button clicked")

  // Show the popup preview container first
  jQuery("#popup-preview-container").css("display", "block")
  jQuery("body").css("overflow", "hidden") // Prevent scrolling

  // Create the popup preview
  createPopupPreview("mobile")
})

// Handle close preview button click - make sure this works
jQuery(document).on("click", "#close-preview, #preview-close-btn", (e) => {
  e.preventDefault()
  console.log("Close preview button clicked")
  jQuery("#popup-preview-container").css("display", "none")
  jQuery("body").css("overflow", "auto") // Re-enable scrolling
})

// Function to create and show the popup preview
function createPopupPreview(deviceType) {
  console.log("Creating popup preview for: " + deviceType)

  // Generate popup HTML
  var popupHtml = generatePopupHtml(deviceType)

  // Insert the popup HTML directly into the existing preview container
  jQuery("#preview-popup-content").html(popupHtml)

  // Make sure the container is visible
  jQuery("#popup-preview-container").css("display", "block")

  // Start the timer animation
  var countdownSeconds = jQuery("#popup_countdown_seconds").val() || "20"
  if (countdownSeconds > 0) {
    jQuery("#preview-popup-content .c404r-timer-bar").css("animation-duration", countdownSeconds + "s")
  }
}

// Function to generate popup HTML
function generatePopupHtml(deviceType) {
  // Get form values
  var title = jQuery("#popup_title").val() || "IMPORTANT NOTICE: REBRANDING UPDATE"
  var subtitle = jQuery("#popup_subtitle").val() || "This Product Is Now Rebranded Due To Counterfeit Concerns."
  var description =
    jQuery("#popup_description").val() ||
    "The new packaging comes with advanced security features to ensure authenticity, including:"
  var feature1 = jQuery("#popup_feature_1").val() || "Registered Trademark Protection"
  var feature2 = jQuery("#popup_feature_2").val() || "Embedded Security Pattern"
  var feature3 = jQuery("#popup_feature_3").val() || "Tamper-Evident Seal"
  var buttonPrimaryText = jQuery("#popup_button_primary_text").val() || "BUY NOW"
  var buttonSecondaryText = jQuery("#popup_button_secondary_text").val() || "GO BACK"
  var footerText = jQuery("#popup_footer_text").val() || "100% GUARANTEED AUTHENTIC PACKAGING"
  var countdownSeconds = jQuery("#popup_countdown_seconds").val() || "20"
  var imageUrl = jQuery("#popup_image_url").val() || ""

  // Get color values
  var headerColor = jQuery("#popup_header_color").val() || "#e32b2b"
  var subtitleBackgroundColor = jQuery("#popup_subtitle_background_color").val() || "#ffc107"
  var buttonPrimaryColor = jQuery("#popup_button_primary_color").val() || "#ffc107"
  var buttonPrimaryTextColor = jQuery("#popup_button_primary_text_color").val() || "#000000"
  var contentBackgroundColor = jQuery("#popup_content_background_color").val() || "#000000"
  var descriptionTextColor = jQuery("#popup_description_text_color").val() || "#ffffff"
  var bulletTextColor = jQuery("#popup_bullet_text_color").val() || "#ffffff"
  var timerBarColor = jQuery("#popup_timer_bar_color").val() || "#ffc107"

  // Get other settings
  var productBgColor = jQuery("#popup_product_bg_color").val() || "#ffffff"
  var productBgOpacity = jQuery("#popup_product_bg_opacity").val() || "20"
  var productBgRadius = jQuery("#popup_product_bg_radius").val() || "none"
  var buttonWidth = jQuery("#popup_button_width").val() || "100"
  var buttonStyle = jQuery("#popup_button_style").val() || "flat"
  var enableGlare = jQuery("#popup_enable_glare").is(":checked")

  // Get font sizes - map to actual pixel values
  var fontSizeMap = {
    xsmall: { desktop: "16px", mobile: "14px" },
    small: { desktop: "18px", mobile: "16px" },
    medium: { desktop: "24px", mobile: "18px" },
    large: { desktop: "28px", mobile: "20px" },
    xlarge: { desktop: "32px", mobile: "22px" },
  }

  var descFontSizeMap = {
    xsmall: { desktop: "10px", mobile: "8px" },
    small: { desktop: "12px", mobile: "10px" },
    medium: { desktop: "16px", mobile: "12px" },
    large: { desktop: "18px", mobile: "14px" },
    xlarge: { desktop: "20px", mobile: "16px" },
  }

  var titleFontSizeValue,
    subtitleFontSizeValue,
    descriptionFontSizeValue,
    featuresFontSizeValue,
    buttonFontSizeValue,
    footerFontSizeValue

  // Get selected font size values
  var titleFontSize = jQuery("#popup_title_font_size").val() || "medium"
  var subtitleFontSize = jQuery("#popup_subtitle_font_size").val() || "medium"
  var descriptionFontSize = jQuery("#popup_description_font_size").val() || "medium"
  var featuresFontSize = jQuery("#popup_features_font_size").val() || "medium"
  var buttonFontSize = jQuery("#popup_button_font_size").val() || "medium"
  var footerFontSize = jQuery("#popup_footer_font_size").val() || "medium"

  var titleFontSizeMobile = jQuery("#popup_title_font_size_mobile").val() || "medium"
  var subtitleFontSizeMobile = jQuery("#popup_subtitle_font_size_mobile").val() || "medium"
  var descriptionFontSizeMobile = jQuery("#popup_description_font_size_mobile").val() || "medium"
  var featuresFontSizeMobile = jQuery("#popup_features_font_size_mobile").val() || "medium"
  var buttonFontSizeMobile = jQuery("#popup_button_font_size_mobile").val() || "medium"
  var footerFontSizeMobile = jQuery("#popup_footer_font_size_mobile").val() || "medium"

  // Set actual font size values based on device type
  if (deviceType === "mobile") {
    titleFontSizeValue = fontSizeMap[titleFontSizeMobile].mobile
    subtitleFontSizeValue = fontSizeMap[subtitleFontSizeMobile].mobile
    descriptionFontSizeValue = descFontSizeMap[descriptionFontSizeMobile].mobile
    featuresFontSizeValue = descFontSizeMap[featuresFontSizeMobile].mobile
    buttonFontSizeValue = descFontSizeMap[buttonFontSizeMobile].mobile
    footerFontSizeValue = descFontSizeMap[footerFontSizeMobile].mobile
  } else {
    titleFontSizeValue = fontSizeMap[titleFontSize].desktop
    subtitleFontSizeValue = fontSizeMap[subtitleFontSize].desktop
    descriptionFontSizeValue = descFontSizeMap[descriptionFontSize].desktop
    featuresFontSizeValue = descFontSizeMap[featuresFontSize].desktop
    buttonFontSizeValue = descFontSizeMap[buttonFontSize].desktop
    footerFontSizeValue = descFontSizeMap[footerFontSize].desktop
  }

  // Handle button style
  var buttonBorderRadius = "4px"
  var buttonBorder = "none"
  var buttonBoxShadow = "none"
  var buttonBackgroundImage = "none"

  switch (buttonStyle) {
    case "rounded":
      buttonBorderRadius = "50px"
      break
    case "outlined":
      buttonBorder = "2px solid " + buttonPrimaryColor
      buttonBackgroundImage = "none"
      buttonPrimaryColor = "transparent"
      break
    case "gradient":
      buttonBackgroundImage = "linear-gradient(to right, " + buttonPrimaryColor + ", #ff9800)"
      buttonPrimaryColor = "transparent"
      break
    case "shadow":
      buttonBoxShadow = "0 4px 6px rgba(0, 0, 0, 0.3)"
      break
    case "3d":
      buttonBoxShadow = "0 4px 0 rgba(0, 0, 0, 0.2)"
      break
    case "neon":
      buttonBoxShadow = "0 0 10px " + buttonPrimaryColor + ", 0 0 20px " + buttonPrimaryColor
      break
    case "glossy":
      buttonBackgroundImage =
        "linear-gradient(to bottom, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 50%, rgba(0, 0, 0, 0.1) 51%, rgba(0, 0, 0, 0.1) 100%)"
      break
    case "shiny":
      buttonBackgroundImage =
        "linear-gradient(-45deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.2) 25%, rgba(255, 255, 255, 0.7) 25%, rgba(255, 255, 255, 0.7) 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, rgba(255, 255, 255, 0) 75%)"
      break
    case "metallic":
      buttonBackgroundImage =
        "linear-gradient(to bottom, rgba(255, 255, 255, 0.6) 0%, rgba(255, 255, 255, 0.4) 47%, rgba(0, 0, 0, 0.05) 53%, rgba(0, 0, 0, 0.1) 100%)"
      break
    case "pill":
      buttonBorderRadius = "50px"
      buttonBoxShadow = "inset 0 -3px 0 rgba(0, 0, 0, 0.15)"
      break
    case "skeuomorphic":
      buttonBorderRadius = "6px"
      buttonBoxShadow = "inset 0 1px 0 rgba(255, 255, 255, 0.4), 0 1px 3px rgba(0, 0, 0, 0.3)"
      buttonBorder = "none"
      buttonBackgroundImage =
        "linear-gradient(to bottom, " + buttonPrimaryColor + " 0%, " + shadeColor(buttonPrimaryColor, -20) + " 100%)"
      break
  }

  // Helper function to darken a color
  function shadeColor(color, percent) {
    var R = Number.parseInt(color.substring(1, 3), 16)
    var G = Number.parseInt(color.substring(3, 5), 16)
    var B = Number.parseInt(color.substring(5, 7), 16)

    R = Number.parseInt((R * (100 + percent)) / 100)
    G = Number.parseInt((G * (100 + percent)) / 100)
    B = Number.parseInt((B * (100 + percent)) / 100)

    R = R < 255 ? R : 255
    G = G < 255 ? G : 255
    B = B < 255 ? B : 255

    var RR = R.toString(16).length == 1 ? "0" + R.toString(16) : R.toString(16)
    var GG = G.toString(16).length == 1 ? "0" + G.toString(16) : G.toString(16)
    var BB = B.toString(16).length == 1 ? "0" + B.toString(16) : B.toString(16)

    return "#" + RR + GG + BB
  }

  // Create popup HTML with timer bar animation
  var popupHtml = ""

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
  `

  if (deviceType === "mobile") {
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
    `
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
          <p style="margin:   text-align: center; background-color: ${subtitleBackgroundColor};">
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
    `
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
    ` + popupHtml
  }

  return popupHtml
}
