jQuery(document).ready(function ($) {
  // Function to update SKU preview
  function updatePreview() {
    const formData = new FormData();
    formData.append("action", "get_sku_preview");
    formData.append("nonce", skuGeneratorAjax.nonce);

    // Add all form fields
    $("form input, form select").each(function () {
      if ($(this).is(":checkbox")) {
        formData.append(
          $(this)
            .attr("name")
            .replace("sku_generator_options[", "")
            .replace("]", ""),
          $(this).is(":checked") ? "1" : "0"
        );
      } else {
        formData.append(
          $(this)
            .attr("name")
            .replace("sku_generator_options[", "")
            .replace("]", ""),
          $(this).val()
        );
      }
    });

    $.ajax({
      url: skuGeneratorAjax.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          $("#sku-preview").text(response.data.preview);
        }
      },
    });
  }

  // Update preview on form field changes
  $("form input, form select").on(
    "change keyup",
    _.debounce(updatePreview, 500)
  );

  // Initial preview update
  updatePreview();

  // Check products without SKU
  $("#check-products").on("click", function (e) {
    e.preventDefault();
    const $button = $(this);
    const $count = $("#products-count");

    $button.prop("disabled", true);
    $count.html(
      '<span class="spinner is-active" style="float: none; margin: 0 5px;"></span> Checking...'
    );

    $.ajax({
      url: skuGeneratorAjax.ajaxurl,
      type: "POST",
      data: {
        action: "get_products_without_sku",
        nonce: skuGeneratorAjax.nonce,
      },
      success: function (response) {
        if (response.success) {
          $count.text(response.data.message);
        } else {
          $count.text("Error checking products");
        }
        $button.prop("disabled", false);
      },
      error: function () {
        $count.text("Error checking products");
        $button.prop("disabled", false);
      },
    });
  });

  // Generate SKUs
  $("#generate-skus").on("click", function (e) {
    e.preventDefault();

    const $button = $(this);
    const $progress = $("#progress-bar");
    const $progressBar = $progress.find("progress");
    const $progressText = $("#progress-text");

    $button.prop("disabled", true);
    $progress.show();

    function generateSKUs(offset = 0) {
      $.ajax({
        url: skuGeneratorAjax.ajaxurl,
        type: "POST",
        data: {
          action: "generate_bulk_skus",
          nonce: skuGeneratorAjax.nonce,
          offset: offset,
        },
        success: function (response) {
          if (response.success) {
            if (response.data.complete) {
              $progressBar.val(100);
              $progressText.text("100%");
              $button.prop("disabled", false);
              alert(response.data.message);
              // Refresh the count after generation
              $("#check-products").trigger("click");
            } else {
              $progressBar.val(response.data.progress);
              $progressText.text(response.data.progress + "%");
              // Continue with next batch
              generateSKUs(response.data.offset);
            }
          } else {
            alert("Error generating SKUs. Please try again.");
            $button.prop("disabled", false);
          }
        },
        error: function () {
          alert("Error generating SKUs. Please try again.");
          $button.prop("disabled", false);
        },
      });
    }

    generateSKUs();
  });
});
