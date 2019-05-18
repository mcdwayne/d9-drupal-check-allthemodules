(function ($, Drupal) {
    Drupal.behaviors.commercePagseguroTransparente = {
        attach: function (context, settings) {
            loadEvents();
            $("#edit-commerce-payment-payment-method-pagseguro-transparentecommerce-payment-pagseguro-transparente").ajaxComplete(function(event,request, settings) {
                loadEvents();
            });
        }
    };

    var currentBrand = "visa";

    var loadEvents = function() {
      // Initialize without the sessionId.
      var hasSessionId = false;
      updateSessionId();
      installmentQuantityEvents();
      updateInstallments(currentBrand);

      // Create tokens on form updates.
      $("#credit-card-number").change(function() {
        var cardBin = $("#credit-card-number").val();
        updateCardBrand(cardBin);
        updateTokens();
      });
      $("#security-code").change(function() {
        updateTokens()
      });
      $("#expiration-month").change(function() {
        updateTokens()
      });
      $("#expiration-year").change(function() {
        updateTokens()
      });
    };

    var updateTokens = function() {
      if (
        $("#credit-card-number").val()
        && $("#security-code").val()
      ) {
        updateSenderHash();
        updateCardToken();
      }
    };

    var updateSenderHash = function() {
      var senderHash = PagSeguroDirectPayment.getSenderHash();
      $("#sender-hash").val(senderHash);
    };

    var updateSessionId = function(callback) {
      PagSeguroDirectPayment.setSessionId(drupalSettings.commercePagseguro.sessionId);
    };

    // Atualiza dados de parcelamento atráves da bandeira do cartão
    var updateInstallments = function(brand) {

      var amount = Number(drupalSettings.commercePagseguro.orderAmount);
      var noInsterest = drupalSettings.commercePagseguro.maxInstallmentNoInterest;

      PagSeguroDirectPayment.getInstallments({
        amount: amount,
        brand:  brand,
        maxInstallmentNoInterest: noInsterest,
        success: function(response) {

          // Para obter o array de parcelamento use a bandeira como "chave" da lista "installments"
          var installments = response.installments[brand];

          var options = ('<option value="0" dataPrice="0">-- Selecione --</option>');
          for (var i in installments) {

            var optionItem     = installments[i];
            var optionQuantity = optionItem.quantity; // Obtendo a quantidade
            var optionAmount   = optionItem.installmentAmount; // Obtendo o valor
            var optionLabel    = (optionQuantity + "x de " + formatMoney(optionAmount)); // montando o label do option

            if (i == 0) {
              var optionLabel = formatMoney(optionAmount);
              optionLabel += " à vista";
            }

            if (i > 0 && i < noInsterest) {
              optionLabel += " sem juros";
            }
            var price = Number(optionAmount).toMoney(2,'.',',');
            options += ('<option value="' + optionItem.quantity + '" dataPrice="' + price + '">'+ optionLabel +'</option>');

          };

          // Atualizando dados do select de parcelamento
          $("#installments").html(options).prop("disabled", false);

          // Utilizando evento "change" como gatilho para atualizar o valor do parcelamento
          $("#installments").trigger('change');

        },
        error: function(response) {
          console.log(response);
        },
        complete: function(response) {

        }
      });

    };

    var updateCardBrand = function(cardBin) {

        PagSeguroDirectPayment.getBrand({

          cardBin: cardBin,

          success: function(response) {

            var brand = response.brand.name;

            if (currentBrand != brand) {
              currentBrand = brand;
              updateInstallments(brand);
            }

          },

          error: function(response) {

          },

          complete: function(response) {

          }

        });

      };

    var updateCardToken = function() {

      PagSeguroDirectPayment.createCardToken({

        cardNumber: $("#credit-card-number").val(),
        brand: currentBrand,
        cvv: $("#security-code").val(),
        expirationMonth: $("#expiration-month").val(),
        expirationYear: $("#expiration-year").val(),

        success: function(response) {
          // Obtendo token para pagamento com cartão
          var token = response.card.token;
          $("#card-token").val(token);
        },

        error: function(response) {
          showCardTokenErrors(response.errors);
        },

        complete: function(response) {

        }

      });
    };

    // Atualizando o valor do parcelamento
    var installmentQuantityEvents = function() {
      jQuery("#installments").change(function() {
        updateTokens();
        var option = jQuery(this).find("option:selected");
        if (option.length) {
          jQuery("#installments").val(option.attr("dataPrice"));
        }
      });
    };

    // Shipping specific stuff
    var shippingEvents = function() {
      jQuery("[name='commerce_shipping[shipping_service]']").change(function() {
        var option = jQuery(this).find("checked:checked");
        if (option.length) {
          jQuery("[name='commerce_shipping[shipping_service]").val( option.attr("dataPrice") );
        }
      });
    };
})(jQuery, Drupal);