/**
 * @file
 * Drupal Customer's Canvas javascript integration.
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.customersCanvas = {
    attach: function (context, settings) {
      // Only run on AttachBehaviors when the #cc_wrapper is part of context.
      $('#cc_wrapper', context).once('cc_wrapper').each(function () {
        // Load Drupal-based settings.
        // If we have a state_id type url, do not parse it.
        if (settings.customersCanvas.productJson.charAt(0) === "{") {
          var productDefinition = JSON.parse(settings.customersCanvas.productJson);
        } else {
          var productDefinition = settings.customersCanvas.productJson;
        }
        var config = JSON.parse(settings.customersCanvas.builderJson);
        // Load JS-required settings.
        var iframe = document.getElementById("editorFrame");
        var editor = null;
        // Loading the editor.
        CustomersCanvas.IframeApi.loadEditor(iframe, productDefinition, config)
        // If the editor has been successfully loaded.
          .then(function (e) {
            editor = e;
          })
          // If there was an error thrown when loading the editor.
          .catch(function (error) {
            console.error("The editor failed to load with an exception: ", error);
          });
        $("#editorFinish", context).click(function(e) {
          e.preventDefault();
          // Completing product customization.
          editor.finishProductDesign()
          // If product customization is completed successfully.
            .then(function (result) {
              // Save result to form.
              $("#customers-canvas-finish input[name=result]", context)
                .attr("value", JSON.stringify(result));
              // Submit form.
              $("#customers-canvas-finish", context).submit();
            })
            // If there was an error thrown when completing product customization.
            .catch(function (error) {
              console.error("Completing product customization failed with exception: ", error);
            });
        });
      });
    }
  }
})(jQuery, Drupal);
