/**
 * @file
 * Javascript to generate Payone Pseudo-PAN token in PCI-compliant way.
 */

(function ($, Drupal, drupalSettings, Payone) {
  Drupal.behaviors.commercePayoneForm = {

    attach: function (context) {
      var $payone_form = $('.payone-form', context);
      var iframes;
      var $form = $('.payone-form', context).closest('form');
      $payone_form.once('setup').each(function () {
          var supportedCardtypes = drupalSettings.commercePayone.allowed_cards;
          var supportedCardsMap = drupalSettings.commercePayone.allowed_cards_map;
          var request = drupalSettings.commercePayone.request;
          var config = {
            fields: {
              cardpan: {
                selector: "cardpan",
                style: "font-size: 16px; border: 1px solid #d5dee3;",
                type: "input",
                iframe: {
                  width: "100%"
                },
              },
              cardcvc2: {
                selector: "cardcvc2",
                type: "text", // Could be "text" as well.
                style: "font-size: 16px; border: 1px solid #d5dee3;",
                size: "4",
                maxlength: "4",
                iframe: {
                  width: "100%"
                },
                length: { "V": 3, "M": 3 } // enforce 3 digit CVC für VISA and Mastercard
              },
              cardexpiremonth: {
                selector: "cardexpiremonth",
                type: "select",
                maxlength: "2",
                iframe: {
                  width: "50px"
                },
                style: "font-size: 16px; width: 50px; border: solid 1px #d5dee3; height: 22px;"
              },
              cardexpireyear: {
                selector: "cardexpireyear",
                type: "select",
                width: "70px",
                style: "font-size: 16px; width: 70px; border: solid 1px #d5dee3; height: 22px;"
              },
              cardtype: {
                selector: "cardtype",
                cardtypes: supportedCardtypes,
              },
            },
            defaultStyle: {
              input: "font-size: 1em; border: 1px solid #d5dee3; width: 175px;",
              select: "font-size: 1em; border: 1px solid #d5dee3;",
              iframe: {
                height: "30px",
                width: "180px"
              }
            },
            autoCardtypeDetection: {
              supportedCardtypes: supportedCardtypes,
              callback: function (detectedCardtype) {
                iframes.setCardType(detectedCardtype);
                $('.payment-method-icon').css('borderColor', '#FFF');
                if (supportedCardsMap.hasOwnProperty(detectedCardtype)) {
                  document.getElementById(supportedCardsMap[detectedCardtype]).style.borderColor = '#00F';
                }
              },
            },
            error: "error" // area to display error-messages (optional)
          };
          if (drupalSettings.hasOwnProperty('path')
            && drupalSettings.path.hasOwnProperty('currentLanguage')
            && Payone.ClientApi.Language.hasOwnProperty(drupalSettings.path.currentLanguage)) {
            config.language = Payone.ClientApi.Language[drupalSettings.path.currentLanguage];
          }
          iframes = new Payone.ClientApi.HostedIFrames(config, request);

          $form.submit(function (e) {

            if (iframes.isComplete()) {
              // Perform "CreditCardCheck" to create and get a PseudoCardPan; then call your function
              iframes.creditCardCheck('processPayoneResponse');
            }
            else {
              var $error = $('#error');
              var message = $('<div>');
              if (!iframes.isCardpanComplete()) {
                message.append('<div class="error">' + Drupal.t('Please complete your cardnumber.') +  '</div>');
              }
              if (!iframes.isCvcComplete()) {
                message.append('<div class="error">' + Drupal.t('Please complete your CVC number.') +  '</div>');
              }
              if (!iframes.isExpireMonthComplete()) {
                message.append('<div class="error">' + Drupal.t('Please complete your expire month.') +  '</div>');
              }
              if (!iframes.isExpireYearComplete()) {
                message.append('<div class="error">' + Drupal.t('Please complete your expire year.') +  '</div>');
              }
              $error.append(message);
              $([document.documentElement, document.body]).animate({
                scrollTop: $error.offset().top-250
              }, 500);
            }
            // Disable the submit button to prevent repeated clicks
            $form.find('button').prop('disabled', true);
            // Prevent the form from submitting with the default action
            return false;
          });
        }
      );
    }
  };
})(jQuery, Drupal, drupalSettings, Payone);

// Outside of Drupal behaviours because ajax.js calls callback using global context.
function processPayoneResponse(response) {
  var form = document.getElementById('pseudocardpan');
  while (form.nodeName != "FORM" && form.parentNode) {
    form = form.parentNode;
  }

  if (response.status === 'VALID') {
    document.getElementById("pseudocardpan").value = response.pseudocardpan;
    document.getElementById("truncatedcardpan").value = response.truncatedcardpan;
    document.getElementById("cardtypeResponse").value = response.cardtype;
    document.getElementById("cardexpiredateResponse").value = response.cardexpiredate;
    form.submit();
  }
  else {
    var $error = jQuery('#error');
    jQuery([document.documentElement, document.body]).animate({
      scrollTop: $error.offset().top-250
    }, 500);
  }
}
