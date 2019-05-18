/**
 * @file
 * Defines behaviors for the pagarme modal form.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the pagarmeModal behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the pagarmeModal behavior.
   */
  Drupal.behaviors.pagarmeModal = {
    attach: function (context) {
      $('#pagarme-modal-pay-button').click(function(e) {
        e.preventDefault();

        // Iniciar a instância do checkout e declarar um callback de sucesso
        var checkout = new PagarMeCheckout.Checkout({
          "encryption_key": drupalSettings.pagarme.encryption_key,
          success: Drupal.Pagarme.CP.sendSuccesfull
        });

        // Passar pârametros e abrir o modal (é necessário passar os valores boolean em "var params" como string)
        checkout.open(drupalSettings.pagarme.checkout_params);
      });
    }
  };
  var checkout_form = $("form.commerce-checkout-flow");

  checkout_form.ready(function() {
    $("#edit-actions-next").hide();
    $("#edit-buttons").addClass("pagarme-modal");
  });

  Drupal.Pagarme = {};
  Drupal.Pagarme.CP = {};

  //Tratar aqui as ações de callback do checkout, como exibição de mensagem ou envio de token para captura da transação
  Drupal.Pagarme.CP.sendSuccesfull = function(data) {
    Drupal.Pagarme.answer = JSON.stringify(data);
    Drupal.Pagarme.submitCheckoutForm();
  };

  Drupal.Pagarme.submitCheckoutForm = function() {
    if (Drupal.Pagarme.answer.length !== 0) {
      checkout_form.find('[name="payment_process[offsite_payment][pagarme_modal][answer]"]').val(Drupal.Pagarme.answer);
      checkout_form.submit();
    }
  };
})(jQuery, Drupal, drupalSettings);