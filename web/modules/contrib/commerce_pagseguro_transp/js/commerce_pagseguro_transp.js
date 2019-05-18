(function ($, Drupal) {
  Drupal.behaviors.commercePagseguroTransparente = {
    attach: function (context, settings) {

      // Elimina as chamadas desse scrip por outros elementos que não o
      // document. Esse script estava sendo chamado pelo administrator menu e
      // outro módulo.
      if (context !== document) {
        return;
      }

      // Cria a div do overlay do pagamento sendo processado
      $('body').append("<div id='checkout-loading-wrapper' style='display: none'><div id='checkout-loading'><span id='checkout-image'></span><span id='checkout-text'>O seu pagamento está sendo processado. Isso pode demorar um pouco. Por Favor, Aguarde...</span></div></div>");
      // Esconde a div criada. Ela só será mostrada quando clicar no botão de
      // pagar
      $('#checkout-loading-wrapper').hide();

      // Getting variables session passed on PaymentMethodAddForm.php
      var session = drupalSettings.commercePagseguroTransparente.commercePagseguro.session;
      PagSeguroDirectPayment.setSessionId(session);

      // Remove attribute name of critical data
      $('#credit-card-number').removeAttr('name');
      $('#security-code').removeAttr('name');
      $('#expiration-month').removeAttr('name');
      $('#expiration-year').removeAttr('name');

      $('#credit-card-number').after("<div id='card-brand'></div>");

      // Inclui no evento onSubmit do formulário tratamento dos termos de uso e
      // a chamada de pagamento por cartão de crédito e boleto, de acordo com a
      // opção selecionada Pelo usuário
      $('.commerce-checkout-flow').submit(function (event) {
        // Evita o submit default
        event.preventDefault();

        // Mostra o overlay com a mensagem de pagamento sendo processado
        $('#checkout-loading-wrapper').show();
        $('button[type=submit], input[type=submit]').prop('disabled', true);

        // Call function paySuccess according payment type
        switch ($('#payment-type').val()) {
          case 'pagseguro_credit':
            // Se o tipo do cartão estiver vazio, faz uma consulta para
            // preencher o formulário com o cartão certo.
            if ($('#payment-method-id').val() === '') {
              var creditCardNumber = getCreditCardNumber();
              PagSeguroDirectPayment.getBrand({
                cardBin: creditCardNumber,
                success: function (response) {
                  $('#payment-method-id').val(response['brand']['name']);
                },
                error: function (response) {
                }
              });
            }

            var brand;
            brand = $('#payment-method-id').val();

            var param = {
              cardNumber: $('#credit-card-number').val(),
              cvv: $('#security-code').val(),
              expirationMonth: $('#expiration-month').val(),
              expirationYear: $('#expiration-year').val(),
              brand: brand,
              success: function (response) {
                paySuccess(response);
              },
              error: function (response) {
                payError(response);
                // tratamento do erro
              },
              complete: function (response) {
                // tratamento comum para todas chamadas
              }
            };

            PagSeguroDirectPayment.createCardToken(param);
            break;
          case 'pagseguro_ticket':
            paySuccess(null);
            break;
          case 'pagseguro_debit':
            paySuccess(null);
            break;
        }
      });

      // Listening event keyup of credit card field
      $('#credit-card-number').keyup(function () {
        getPaymentMethod('keyup');
        clearOptions();
      });

      // Getting credit card flag
      function getPaymentMethod(event) {
        var bin = getCreditCardNumber();
        if (event === 'keyup') {
          if (bin.length === 6) {
            PagSeguroDirectPayment.getBrand({
              cardBin: bin,
              success: function (response) {
                setPaymentMethodInfo(response);
              },
              error: function (response) {
                alert('Número do cartão inválido.');
              }
            });
          }
          else if (bin.length < 6) {
            $('#card-brand').removeClass();
          }
        }
      }

      // Getting credit card number without format
      function getCreditCardNumber() {
        var ccNumber = document.querySelector('#credit-card-number');
        if (ccNumber != null) {
          return ccNumber.value.replace(/[ .-]/g, '').slice(0, 6);
        }
      }

      // Clear Options of Installments select
      function clearOptions() {
        var bin = getCreditCardNumber();
        if (bin.length === 0) {
          var selectorInstallments = document.querySelector('#installments');
          var fragment = document.createDocumentFragment();
          var option = new Option('Escolha a quantidade de parcelas...', '-1');

          selectorInstallments.options.length = 0;
          fragment.appendChild(option);
          selectorInstallments.appendChild(fragment);
          selectorInstallments.setAttribute('disabled', 'disabled');
          $('.js-form-item-payment-information-add-payment-method-payment-details-installments').addClass('form-disabled');
        }
      }

      /* Criando uma div para mostrar a bandeira do cartão digitado. Depois chama  a função
      para trazer as opções de parcelamento */
      function setPaymentMethodInfo(response) {
        $('#payment-method-id').val(response['brand']['name']);

        // Show logo of the payment method after credit card input field
        $('#card-brand').addClass(response['brand']['name']);

        // Pega o valor do pedido que é passado ao Drupal Behaviors no momento
        // da criação formulário do cartão
        var amount = drupalSettings.commercePagseguroTransparente.commercePagseguro.amount;
        var maxInstallmentNoInterest = drupalSettings.commercePagseguroTransparente.commercePagseguro.maxInstallmentNoInterest;
        PagSeguroDirectPayment.getInstallments({
          amount: amount,
          brand: response['brand']['name'],
          maxInstallmentNoInterest: maxInstallmentNoInterest,
          success: function (response) {
            setInstallmentInfo(response);
          },
          error: function (response) {
            //alert('Deu erro!');
          }
        });
      }

      /* De acordo com o cartão escolhido, verifica as opções de parcelamento.
      Preenche o select com essas opções.
      */
      function setInstallmentInfo(response) {
        var selectorInstallments = document.querySelector('#installments');
        var fragment = document.createDocumentFragment();

        selectorInstallments.options.length = 0;
        selectorInstallments.options.selectedIndex = 0;

        branding = response.installments;
        for (var key in branding) {
          if (branding.hasOwnProperty(key)) {
            keys = branding[key];
          }
        }

        if (keys.length > 0) {
          var txtSingularPlural = 'parcela';

          for (var i = 0; i < keys.length; i++) {
            if (keys[i].quantity > 1) {
              txtSingularPlural = 'parcelas';
            }
            txt = keys[i].quantity + ' ' + txtSingularPlural + ' de R$' + numberToCurrency(keys[i].installmentAmount, 2, ',', '.') + ' (R$' + numberToCurrency(keys[i].totalAmount, 2, ',', '.') + ')';
            option = new Option(txt, keys[i].quantity);
            // Adiciona o valor da parcela como um atributo da opção, pois será
            // necessário saber quando for enviar o pedido.
            option.setAttribute('installmentAmount', keys[i].installmentAmount);
            fragment.appendChild(option);
          }
          selectorInstallments.appendChild(fragment);
          selectorInstallments.removeAttribute('disabled');
          $('.js-form-item-payment-information-add-payment-method-payment-details-installments').removeClass('form-disabled');
        }
      }

      /* Chamada quando o token do cartão de crédito é gerado com sucesso. Cria um input hiden
      para colocar o valor do token dentro e passar via post */
      function paySuccess(response) {
        // todo: Verificar se existe somente esse id de formulário, ou se
        // mudando o workflow esse id muda.
        var form = document.querySelector('.commerce-checkout-flow');

        if (response != null) {
          // Cria um input do tipo hidden para colocar o token
          $('#card-token').val(response.card.token);
        }

        var hash = PagSeguroDirectPayment.getSenderHash();

        // Cria um input do tipo hidden para colocar o hash do usuário
        $('#sender-hash').val(hash);

        // Passa a quantidade de parcelas e o valor da parcela para os
        // formulários
        var e = document.getElementById('installments');

        if (e) {
          if (e.options.selectedIndex !== 0) {
            var option = e.options[e.selectedIndex];
            $('#installment-amount').val(option.getAttribute('installmentAmount'));
            $('#installments-qty').val(option.getAttribute('value'));
          }
        }

        // Chama o submit para capturar esses resultados no php
        form.submit();
      }

      // todo: alterar os ids para os ids corretos. Criar as divs necessárias
      function payError(response) {
        $('button[type=submit], input[type=submit]').prop('disabled', false);
        $('#checkout-loading-wrapper').hide();
        $("[role='alert']").remove();
        $('.payment-information-wrapper.error').removeClass('error');

        $.extend(Drupal.theme, /** @lends Drupal.theme */{
          commercePagseguroError: function (message) {
            return $('<div role="alert">' +
                '<div class="messages messages--error">' + message + '</div>' +
                '</div>'
            );
          }
        });


        // Tratando os erros na criação do token do cartão
        var erroMessages = '<ul>';
        cause = response.errors;
        for (var key in cause) {
          if (!cause.hasOwnProperty(key)) {
            continue;
          }

          switch (key) {
            case '10000':
              erroMessages += '<li>Bandeira do Cartão Inválida.</li>';
              $('#credit-card-number').addClass('error');
              break;
            case '10001':
              erroMessages += '<li>Número do cartão de crédito com o tamanho inválido.</li>';
              $('#credit-card-number').addClass('error');
              break;
            case '10002':
              erroMessages += '<li>Data com um formato inválido.</li>';
              $('#expiration-month').addClass('error');
              $('#expiration-year').addClass('error');
              break;
            case '10003':
              erroMessages += '<li>Código de segurança inválido.</li>';
              $('#security-code').addClass('error');
              break;
            case '10004':
              erroMessages += '<li>O Código de Segurança é obrigatório.</li>';
              $('#security-code').addClass('error');
              break;
            case '10006':
              erroMessages += '<li>Código de Segurança com o tamanho inválido.</li>';
              $('#security-code').addClass('error');
              break;
            case '30400':
              erroMessages += '<li>Número do Cartão de Crédito Inválido.</li>';
              $('#credit-card-number').addClass('error');
              break;
            default:
              erroMessages += '<li>' + cause[key] + '.</li>';
              break;
          }
        }

        erroMessages += '</ul>';
        $('.commerce-checkout-flow').prepend(Drupal.theme('commercePagseguroError', erroMessages));

        $('html, body').animate({
          scrollTop: $('.messages--error').offset().top
        }, 200);
      }

      /*
      n = numero a converter
      c = numero de casas decimais
      d = separador decimal
      t = separador milhar
      */
      function numberToCurrency(n, c, d, t) {
        c = isNaN(c = Math.abs(c)) ? 2 : c, d = d === undefined ? ',' : d, t = t === undefined ? '.' : t, s = n < 0 ? '-' : '', i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + '', j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
      }
    }
  };
})(jQuery, Drupal);
