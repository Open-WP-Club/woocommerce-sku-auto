jQuery(document).ready(function ($) {
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
