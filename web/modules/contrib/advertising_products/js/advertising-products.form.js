(function($, Drupal) {
  Drupal.behaviors.advertisingProductsForm = {
    attach: function(context, settings) {
      $(
        ".advertising-products-autocomplete.form-autocomplete"
      ).on("input", function(e) {
        $(e.target).removeClass("status-red");
      });
    }
  };
})(jQuery, Drupal);
