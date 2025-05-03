jQuery(document).ready(($) => {
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
      $("#image_preview").html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">')
    })

    // Finally, open the modal
    image_frame.open()
  })

  // Update preview when form fields change
  $("input, textarea, select").on("change keyup", function () {
    console.log("Form field changed:", $(this).attr("id"))
  })
})
