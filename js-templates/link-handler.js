jQuery(document).ready(($) => {
  // Get the site URL and default link from the localized data
  var siteUrl = category404RedirectorData.siteUrl
  var defaultLink = category404RedirectorData.defaultLink
  var ajaxUrl = category404RedirectorData.ajaxUrl
  var nonce = category404RedirectorData.nonce
  var popupEnabled = category404RedirectorData.popupEnabled
  var forcePopup = category404RedirectorData.forcePopup
  var popupData = category404RedirectorData.popupData

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
      // If URL doesn't start with site URL
      normalizedUrl.indexOf(normalizedSiteUrl) !== 0 &&
      // And URL doesn't contain the site domain (for www or http cases)
      normalizedUrl.indexOf("://" + siteDomain) === -1 &&
      // And it's an http/https URL
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
            if (popupEnabled && !response.data.is_excluded && forcePopup) {
              // Show popup
              jQuery("body").append(createPopupHtml())
              jQuery("#c404r-popup-overlay").show()
            } else {
              window.location.href = fullUrl
            }
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
    '.entry-content a[href="#"], .post-content a[href="#"], article .content a[href="#"], .single-post .post a[href="#"]',
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
    '.entry-content a[href=""], .post-content a[href=""], article .content a[href=""], .single-post .post a[href=""], .type-post .entry a[href=""]',
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
    '.entry-content a[href="javascript:void(0)"], .post-content a[href="javascript:void(0)"], article .content a[href="javascript:void(0)"], .single-post .post a[href="javascript:void(0)"]',
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

  // Function to create popup HTML
  function createPopupHtml() {
    // Get popup settings from localized data
    var title = popupData.title || "IMPORTANT NOTICE: REBRANDING UPDATE"
    var subtitle = popupData.subtitle || "This Product Is Now Rebranded Due To Counterfeit Concerns."
    var description =
      popupData.description ||
      "The new packaging comes with advanced security features to ensure authenticity, including:"
    var feature1 = popupData.feature1 || "Registered Trademark Protection"
    var feature2 = popupData.feature2 || "Embedded Security Pattern"
    var feature3 = popupData.feature3 || "Tamper-Evident Seal"
    var buttonPrimaryText = popupData.buttonPrimaryText || "BUY NOW"
    var buttonSecondaryText = popupData.buttonSecondaryText || "GO BACK"
    var footerText = popupData.footerText || "100% GUARANTEED AUTHENTIC PACKAGING"
    var countdownSeconds = popupData.countdownSeconds || 20
    var imageUrl = popupData.imageUrl || ""

    // Get color values
    var backgroundColor = popupData.backgroundColor || "#e32b2b"
    var headerColor = popupData.headerColor || "#e32b2b"
    var subtitleBackgroundColor = popupData.subtitleBackgroundColor || "#ffc107"
    var buttonPrimaryColor = popupData.buttonPrimaryColor || "#ffc107"
    var buttonPrimaryTextColor = popupData.buttonPrimaryTextColor || "#000000"
    var contentBackgroundColor = popupData.contentBackgroundColor || "#000000"
    var descriptionTextColor = popupData.descriptionTextColor || "#ffffff"
    var bulletTextColor = popupData.bulletTextColor || "#ffffff"

    // Get other settings
    var productBgColor = popupData.productBgColor || "#ffffff"
    var productBgOpacity = popupData.productBgOpacity || 20
    var productBgRadius = popupData.productBgRadius || "none"
    var buttonWidth = popupData.buttonWidth || 100
    var buttonStyle = popupData.buttonStyle || "flat"
    var enableGlare = popupData.enableGlare || false

    // Get font sizes
    var titleFontSize = popupData.titleFontSize || "medium"
    var subtitleFontSize = popupData.subtitleFontSize || "medium"
    var descriptionFontSize = popupData.descriptionFontSize || "medium"
    var featuresFontSize = popupData.featuresFontSize || "medium"
    var buttonFontSize = popupData.buttonFontSize || "medium"
    var footerFontSize = popupData.footerFontSize || "medium"

    // Get mobile font sizes
    var titleFontSizeMobile = popupData.titleFontSizeMobile || "medium"
    var subtitleFontSizeMobile = popupData.subtitleFontSizeMobile || "medium"
    var descriptionFontSizeMobile = popupData.descriptionFontSizeMobile || "medium"
    var featuresFontSizeMobile = popupData.featuresFontSizeMobile || "medium"
    var buttonFontSizeMobile = popupData.buttonFontSizeMobile || "medium"
    var footerFontSizeMobile = popupData.footerFontSizeMobile || "medium"

    // Create popup HTML
    var html = `
      <div id="c404r-popup-overlay" class="c404r-popup-overlay">
        <div class="c404r-popup c404r-title-${titleFontSize} c404r-subtitle-${subtitleFontSize} c404r-description-${descriptionFontSize} c404r-features-${featuresFontSize} c404r-button-${buttonFontSize} c404r-footer-${footerFontSize} c404r-title-mobile-${titleFontSizeMobile} c404r-subtitle-mobile-${subtitleFontSizeMobile} c404r-description-mobile-${descriptionFontSizeMobile} c404r-features-mobile-${featuresFontSizeMobile} c404r-button-mobile-${buttonFontSizeMobile} c404r-footer-mobile-${footerFontSizeMobile}">
          <div class="c404r-top-bar" style="background-color: ${subtitleBackgroundColor};"></div>
          
          <div class="c404r-header" style="background-color: ${headerColor};">
            <div class="c404r-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <h2>${title}</h2>
            
            <div class="c404r-controls-container">
              <div class="c404r-timer-controls">
                <button class="c404r-back-btn">${buttonSecondaryText}</button>
                <div class="c404r-countdown">${countdownSeconds}s</div>
                <button class="c404r-pause-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <rect x="6" y="4" width="4" height="16"></rect>
                    <rect x="14" y="4" width="4" height="16"></rect>
                  </svg>
                </button>
              </div>
              <button class="c404r-close-btn">×</button>
            </div>
          </div>
          
          <div class="c404r-subtitle" style="background-color: ${subtitleBackgroundColor};">
            <p>${subtitle}</p>
          </div>
          
          <div class="c404r-content" style="background-color: ${contentBackgroundColor};">
            <div class="c404r-content-inner">
              ${
                imageUrl
                  ? `
              <div class="c404r-product-image-container">
                <div class="c404r-product-image">
                  <div class="c404r-product-bg c404r-product-bg-radius-${productBgRadius}" style="background-color: ${productBgColor}; opacity: ${productBgOpacity / 100};"></div>
                  <img src="${imageUrl}" alt="Product Image">
                </div>
              </div>
              `
                  : ""
              }
              
              <div class="c404r-content-text">
                <p style="color: ${descriptionTextColor};">${description}</p>
                
                <ul class="c404r-features">
                  <li style="color: ${bulletTextColor};">
                    <span class="c404r-check-icon">✓</span>
                    ${feature1}
                  </li>
                  <li style="color: ${bulletTextColor};">
                    <span class="c404r-check-icon">✓</span>
                    ${feature2}
                  </li>
                  <li style="color: ${bulletTextColor};">
                    <span class="c404r-check-icon">✓</span>
                    ${feature3}
                  </li>
                </ul>
                
                <a href="${defaultLink}" class="c404r-primary-btn c404r-btn-style-${buttonStyle}" style="background-color: ${buttonPrimaryColor}; color: ${buttonPrimaryTextColor}; width: ${buttonWidth}%;">
                  ${enableGlare ? '<div class="c404r-btn-glare"></div>' : ""}
                  ${buttonPrimaryText}
                </a>
              </div>
            </div>
          </div>
          
          <div class="c404r-footer" style="background-color: ${headerColor};">
            <p>${footerText}</p>
          </div>
        </div>
      </div>
    `

    return html
  }
})
