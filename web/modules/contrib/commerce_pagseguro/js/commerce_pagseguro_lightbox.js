(function ($, Drupal) {
  Drupal.behaviors.commercePagseguroLightbox = {
    attach: function (context, settings) {
      PagSeguroLightbox({
          code: drupalSettings.commercePagseguro.sessionId
          }, {
          success : function(transactionCode) {
              $("[name='transaction_code']").attr("value", transactionCode);
              $("#commerce-checkout-flow-multistep-default").submit();
          },
          abort : function() {
              alert("There was an error processing your payment.");
          }
      });
    }
  };
})(jQuery, Drupal);