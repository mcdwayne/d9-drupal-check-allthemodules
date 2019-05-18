/**
 * @file
 * Provide javascript features of currency converter block.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";
  $.fn.inverseInput = function () {
    var from = $('.easy-currency-con-from-input').val();
    var to = $('.easy-currency-con-to-input').val();
    $('.easy-currency-con-from-input').val(to);
    $('.easy-currency-con-to-input').val(from);
    if (from && to) {
      getConversion(to, from);
    }
  }
  function getConversion(from, to) {
    var path = "http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.xchange%20where%20pair%20in%20%28%22" + from + to + "%22%29&env=store://datatables.org/alltableswithkeys&format=json";
    $.ajax({
      url: path,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        if (data.query.results.rate.Rate && data.query.results.rate.Rate != 'N/A') {
          var rate = data.query.results.rate.Rate;
          var amount = $('.easy-currency-con-amount').val();
          $('.easy-currency-con-rate').val(rate * amount);
        }

      }
    });
  }
  Drupal.behaviors.easy_currency_con_form = {
    attach: function (context, settings) {

        $('.easy-currency-con-amount').keypress(function(e) {
          var number = String.fromCharCode(e.which);
          if (!number.match(/[.0-9]/)) {
            alert('Only numeric value allowed');
            return false;
          }
          else {
            if ($('.easy-currency-con-from-input').val() && $('.easy-currency-con-to-input').val()) {
              getConversion($('.easy-currency-con-from-input').val(), $('.easy-currency-con-to-input').val());
            }
          }
        });

        $("input.easy-currency-con-from-input").autocomplete({
          select: function(event, ui){
            var from_value = ui.item.value;
            if (from_value && (from_value != 'N/A') && $('.easy-currency-con-to-input').val()) {
              getConversion($(this).val(), $('.easy-currency-con-to-input').val());
            };
          }
        });
        $("input.easy-currency-con-to-input").autocomplete({
          select: function(event, ui){
            var to_value = ui.item.value;
            if (to_value != 'N/A') {
              getConversion($('.easy-currency-con-from-input').val(), to_value);
            }
          }
        });

    }
  };
})(jQuery, Drupal, drupalSettings);
