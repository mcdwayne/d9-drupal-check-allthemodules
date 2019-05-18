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
      var self = this;
      var form_id = 'commerce-checkout-flow-multistep-default';
      if ($('#'+form_id).data('drupal-form-fields').match(/edit-payment-process-offsite-payment-pagarme-whitelabel-credit-card-type/g)) {
        $('#edit-actions-next').once('actions-next').click(function(e) {
          e.preventDefault();
          PagarMe.encryption_key = drupalSettings.pagarme.encryption_key;
          // inicializa um objeto de cartão de crédito e completa
          // com os dados do form
          var creditCard = new PagarMe.creditCard();
          var sufix = 'edit-payment-process-offsite-payment-pagarme-whitelabel-credit-card';
          creditCard.cardHolderName = $('#'+sufix+'-name').val();
          creditCard.cardExpirationMonth = $('#'+sufix+'-month-expiration').val();
          creditCard.cardExpirationYear = $('#'+sufix+'-year-expiration').val();
          creditCard.cardNumber = $('#'+sufix+'-number').val();
          creditCard.cardCVV = $('#'+sufix+'-cvv').val();
          // pega os erros de validação nos campos do form
          var fieldErrors = creditCard.fieldErrors();
          //Verifica se há erros
          var hasErrors = false;
          for(var field in fieldErrors) { hasErrors = true; break; }
          if(hasErrors) {
            // realiza o tratamento de errors
            Drupal.Pagarme.UI.creditCardErrorAlert(fieldErrors);
          } else {
            // se não há erros, gera o card_hash...
            creditCard.generateHash(function(cardHash) {
              Drupal.Pagarme.answer = JSON.stringify({
                pagarme_payment_method: 'credit_card',
                card_hash: cardHash
              });
              Drupal.Pagarme.submitCheckoutForm();
            });
          }
          return false;
        });
      }
    }
  };
  var checkout_form = $("form.commerce-checkout-flow");

  checkout_form.ready(function() {
    $("#edit-buttons").addClass("pagarme-credit-card");
  });

  Drupal.Pagarme = {};
  Drupal.Pagarme.CP = {};
  Drupal.Pagarme.UI = {};
  Drupal.Pagarme.UI.messageAlert = function(message) {
    $("#pagarme-cp-messages").empty();
    $("#pagarme-cp-messages").removeClass();
    $("#pagarme-cp-messages").append(message);
    $("#pagarme-cp-messages").show({
      effect: "highlight"
    });
  };

  Drupal.Pagarme.UI.creditCardErrorAlert = function(fieldErrors) {
    var message = '';
    $.each(Object.keys(fieldErrors), function(index, field) {
      switch (field) {
        case 'card_number':
          message += 'Número do cartão inválido.<br />';
          break;
        case 'card_holder_name':
          message += 'Nome do portador inválido.<br />';
          break;
        case 'card_cvv':
          message += 'Código de segurança inválido.<br />';
          break;
      }
      // message += fieldErrors[field] + '<br />';
    });
    Drupal.Pagarme.UI.errorAlert(message);
  };
  Drupal.Pagarme.UI.errorAlert = function(message) {
      Drupal.Pagarme.UI.messageAlert(message);
      $("#pagarme-cp-messages").addClass("messages error");
    };
  //Tratar aqui as ações de callback do checkout, como exibição de mensagem ou envio de token para captura da transação
  Drupal.Pagarme.CP.sendSuccesfull = function(data) {
    Drupal.Pagarme.answer = JSON.stringify(data);
    Drupal.Pagarme.submitCheckoutForm();
  };

  Drupal.Pagarme.submitCheckoutForm = function() {
    if (Drupal.Pagarme.answer.length !== 0) {
      checkout_form.find('[name="payment_process[offsite_payment][pagarme_whitelabel][answer]"]').val(Drupal.Pagarme.answer);
      checkout_form.submit();
    }
  };
})(jQuery, Drupal, drupalSettings);