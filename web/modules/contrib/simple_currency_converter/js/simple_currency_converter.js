(function ($) {
  var scc;

  Drupal.behaviors.simpleCurrencyGlobalSettings = {
    attach: function (context, settings) {
      scc = settings.simple_currency_converter;
    }
  };

  Drupal.behaviors.simpleCurrencyConverterFinalCurrency = {
    attach: function (context, settings) {
      if (typeof scc == 'undefined') {
        return false;
      }

      if (scc.default_conversion_currency == 'none') {
        scc.scc_final_currency = null;
      }

      if (scc.default_conversion_currency != 'none') {
        scc.scc_final_currency = scc.default_conversion_currency;
      }

      var userCurrency = getCookie('scc_user_conversion_currency');
      if (typeof userCurrency != 'undefined') {
        scc.scc_final_currency = userCurrency;
      }
    }
  };

  Drupal.behaviors.simpleCurrencyConverter = {
    attach: function (context, settings) {
      $(document).ready(function() {
        if (!sccPageHasPrice()) {
          return false;
        }
        sccProcess(context);
      });
    }
  };

  Drupal.behaviors.simpleCurrencyConverterModalDialog = {
    attach: function (context, settings) {
      if (typeof scc == 'undefined') {
        return false;
      }

      $(document).ready(function() {
        var $modalWindow = $('#' + scc.window_id, context);

        $modalWindow.dialog({
          autoOpen: false,
          title: scc.window_title,
          resizable: false,
          draggable: false,
          show: {
            effect: 'slideDown'
          },
          hide: {
            effect: 'slideUp'
          },
          beforeClose: function() {
            sccProcess(context);
          }
        });
      });
    }
  };

  Drupal.behaviors.simpleCurrencyConverterModalDialogTrigger = {
    attach: function (context, settings) {
      if (typeof scc == 'undefined') {
        return false;
      }

      var $modalTrigger = $(scc.window_trigger, context);
      var $modalWindow = $('#' + scc.window_id, context);

      $modalTrigger.click(function (event) {
        event.preventDefault();
        $modalWindow.dialog('open');
      });
    }
  };

  Drupal.behaviors.simpleCurrencyConverterQuickLinksListener = {
    attach: function (context, settings) {
      $('.quick-link-swap-currency').click(function (event) {
        event.preventDefault();
        sccUserCurrencyAction($(this).data('currency-code'));
      });
    }
  };

  Drupal.behaviors.simpleCurrencyConverterDropdownListener = {
    attach: function (context, settings) {
      $('.swap-currency').change(function () {
        sccUserCurrencyAction($(this).val());
      });
    }
  };

  function sccPrintConvertedAmount(element, final_currency) {
    var $element = element;
    var element_currency = $element.data('currency-code');
    var price = $element.data('amount');

    if (final_currency) {
      var conversionId = conversionRatioName(element_currency, final_currency);
      var ratio = scc[conversionId];
    }
    else {
      ratio = 1;
      final_currency = element_currency;
    }

    price = sccConvertedAmount(price, ratio);
    var priceText = sccAmountFormat(price, final_currency);
    $element.html(priceText);
  }

  function sccConvertedAmount(price, ratio) {
    var factor = Math.pow(10, 2);
    var output = price * ratio;
    output = output * factor;
    return Math.round(output / factor);
  }

  function sccAmountFormat(price, currency) {
    var country_info = scc.country_info;
    var rules = country_info[currency];
    price = sccAddCurrencySymbol(price, rules);
    price = sccAddCurrencyCode(price, rules);
    return price;
  }

  function sccAddCurrencySymbol(price, rules) {
    if (typeof rules['symbol_placement'] == 'undefined') {
      return price;
    }

    if (rules['symbol_placement'] == 'before') {
      return rules['symbol'] + price;
    }

    if (rules['symbol_placement'] == 'after') {
      return price + rules['symbol'];
    }

    return price;
  }

  function sccAddCurrencyCode(price, rules) {
    return price + ' (' + rules['code'] + ')';
  }

  function sccPageHasPrice() {
    if (typeof scc == 'undefined') {
      return false;
    }

    var $conversion_selector = $(scc.conversion_selector);
    return !!($conversion_selector.length &&
    $conversion_selector.data('amount') &&
    $conversion_selector.data('currency-code'));

  }

  function sccCurrenciesToProcess(context) {
    var currencies = sccCurrenciesOnPage(context);
    var scc_final_currency = scc.scc_final_currency;

    $.each(currencies, function (index, currency) {
      var conversionId = conversionRatioName(currency, scc_final_currency);
      var ratio = getCookie(conversionId);

      if (typeof ratio == 'undefined') {
        sccSetConversionRations(currency);
      }
      else {
        scc[conversionId] = ratio;
      }
    });
  }

  function sccCurrenciesOnPage(context) {
    var $conversion_selector = $(scc.conversion_selector, context);

    var currencies = [];
    $conversion_selector.each(function () {
      var $currencyCode = $(this).data('currency-code');

      if ($.inArray($currencyCode, currencies) === -1) {
        currencies.push($currencyCode);
      }
    });

    return currencies;
  }

  function sccSetConversionRations(from_currency) {
    $.ajax({
      url: '/simple_currency_converter_set_currency/' + from_currency + '/' + scc.scc_final_currency,
      success: function (data) {
        scc[data.name] = data.ratio;
      },
      dataType: 'json',
      cache: false,
      async: false
    });
  }

  function sccProcess(context) {
    var $conversion_selector = $(scc.conversion_selector, context);

    if (typeof scc.scc_final_currency != 'undefined') {
      if (scc.scc_final_currency) {
        sccCurrenciesToProcess(context);
      }

      $conversion_selector.each(function(){
        var $element = $(this);
        sccPrintConvertedAmount($element, scc.scc_final_currency);
      });
    }
  }

  function sccUserCurrencyAction(currency) {
    var $modalWindow = $('#' + scc.window_id);
    scc.scc_final_currency = currency;
    setCookie('scc_user_conversion_currency', scc.scc_final_currency, scc.cookie_expiration);
    $modalWindow.dialog('close');
  }

})(jQuery);

function getCookie(name) {
  var value = "; " + document.cookie;
  var parts = value.split("; " + name + "=");
  if (parts.length == 2) {
    return parts.pop().split(";").shift();
  }
}

function setCookie(name, value, days) {
  var now = new Date();
  now.setTime(now.getTime() + (days * 24 * 60 * 60 * 1000));
  var expires = "expires=" + now.toUTCString();
  document.cookie = name + "=" + value + "; " + expires + "; path=/";
}

function conversionRatioName(from, to) {
  return 'scc_ratio_from_' + from + '_to_' + to;
}
