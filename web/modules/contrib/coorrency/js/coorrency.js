(function ($) {
  Drupal.behaviors.coorrency = {
    attach: function (context, settings) {
      $('#coorrency-block-form').on('submit', function(e) {
        var from = $(this).find('#edit-from').val();
        var to = $(this).find('#edit-to').val();

        if (from == '' || to == '') {
          $('#coorrency-block-form').append('<div class="error">' + Drupal.t('Please fill the form correctly.') + '</div>');

          return false;
        }

        $.ajax({
          type: 'GET',
          url: location.protocol + '//free.currencyconverterapi.com/api/v4/convert?q=' + from + '_' + to + '&compact=y',
          dataType: 'jsonp',
          success: coorrencyJsonParser
        });

        e.preventDefault();
      });

      $('#coorrency-block-form').find('.coorrency-swap').on('click', function() {
        var to = $(this).parent().find('#edit-to').val();

        $(this).parent().find('#edit-to').val($(this).parent().find('#edit-from').val());
        $(this).parent().find('#edit-from').val(to);
      });

      function coorrencyJsonParser(coorrencyJson) {
        var currencyFrom = $('#coorrency-block-form').find('#edit-from').val();
        var currencyTo = $('#coorrency-block-form').find('#edit-to').val();
        var coorrencyRate = coorrencyJson[currencyFrom + '_' + currencyTo].val;

        if ($('#coorrency-block-form').find('#edit-amount').val() != '') {
          var amount = $('#coorrency-block-form').find('#edit-amount').val().replace(',', '');
          if (!isNaN(amount)) {
            coorrencyRate *= amount;
          }
          else {
            var message = Drupal.t('Please enter a number.');
          }
        }

        if (message) {
          $('#coorrency-rate').html('<div class="coorrency-rate-amount"><div class="error">' + message + '</div></div>');
        }
        else {
          $('#coorrency-rate').html('<div class="coorrency-rate-amount">' + (coorrencyRate * 1).toFixed(2) + ' ' + currencyTo + '</div>');
        }
      }
    }
  };
}(jQuery));
