(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.commerceClicForm = {
    attach: function (context) {
      var options = drupalSettings.commerce_clic.transactionData;

      var $paymentForm = $('.payment-redirect-form', context);
      var $submit = $paymentForm.find(':submit');
      var $checkoutHelp = $paymentForm.find('.checkout-help');

      $paymentForm.once('clic-init').each(function () {
        var clic_widget = ClicWidget(options.transaction, 'clic-widget')(options.public_key);

        $checkoutHelp.hide();
        $submit.hide();

        clic_widget.addListener('success', function (response) {
          response['commerce_clic_data'] = options;
          Drupal.behaviors.commerceClicForm.redirectPost(options.return_url, response);
          $checkoutHelp.show();
          $submit.show();
        });

        clic_widget.addListener('failed', function (error) {
          Drupal.behaviors.commerceClicForm.redirectPost(options.cancel_url, error);
        });
      });
    },

    redirectPost(url, data) {
      var form = document.createElement('form');
      document.body.appendChild(form);
      form.method = 'post';
      form.action = url;
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'data';
      input.value = JSON.stringify(data);
      form.appendChild(input);
      form.submit();
    }
  };

})(jQuery, Drupal, drupalSettings);
