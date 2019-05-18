/**
 * @file
 * Drupal Customer's Canvas Multi Editor javascript integration.
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.customersCanvasMultiEditor = {
    attach: function (context, settings) {
      // Get the Customer's Canvas URL ot pass to the settings.
      var ccSettings = {
        customersCanvasUrl: settings.customersCanvas.url
      };

      // Fetch the product, user, and quantity from the settings.
      var product = JSON.parse(settings.customersCanvas.product);
      var user = JSON.parse(settings.customersCanvas.user);
      var quantity = settings.customersCanvas.quantity;

      // The URL's to the files required for the multistep editor.
      require.config({
        baseUrl: '',
        paths: {
          "ecommerce-driver": "https://cdn.jsdelivr.net/npm/@aurigma/ui-framework@3.8.0/dist/driver",
          "uiframework": "https://cdn.jsdelivr.net/npm/@aurigma/ui-framework@3.8.0/dist/editor",
          "text": "https://cdnjs.cloudflare.com/ajax/libs/require-text/2.0.12/text.min",
          "json": "https://cdnjs.cloudflare.com/ajax/libs/requirejs-plugins/1.0.3/json"
        }
      });

      // Combine the productJson and builderJson to form the config object.
      var config = JSON.parse(settings.customersCanvas.config);

      // Specify the URL to the ecommerce driver and uiframework to build the
      // driver.
      require(['ecommerce-driver', 'uiframework'], function (driver, editor) {

        // Initialize the ecommerce driver.
        // @TODO: Create a Drupal Commerce ecommerce driver.
        var ecomdata = driver.init(product, editor, config, ccSettings, null, quantity, user);

        // Display the editor in the specified div element
        ecomdata.products.current.renderEditor($("#aurigma-editor-root")[0]);

        // Add the product to the cart when the user has finished editing.
        ecomdata.orders.current.onSubmitting.subscribe(function (data) {
          // Extract the necessary values from the data, which should include
          // the stateId, userId, and images.
          var result = {
            'proofImageUrls': data.images,
            'hiResOutputUrls': data.downloadUrls,
            'stateId': data.data.stateId,
            'userId': data.data.userId,
          };
          // Save the result to the finish form.
          $("#customers-canvas-finish input[name=result]", context)
            .attr("value", JSON.stringify(result));
          // Submit the form.
          $("#customers-canvas-finish", context).submit();
        });
      });
    }
  }
})(jQuery, Drupal);
