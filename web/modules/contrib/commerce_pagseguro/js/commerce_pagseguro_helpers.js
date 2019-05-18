
  var formatMoney = function(valor) {
    var valorAsNumber = Number(valor);
    return 'R$ ' + valorAsNumber.toMoney(2,',','.');
  };

  Number.prototype.toMoney = function(decimals, decimal_sep, thousands_sep) {
    var n = this,
    c = isNaN(decimals) ? 2 : Math.abs(decimals),
    d = decimal_sep || '.',
    t = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    sign = (n < 0) ? '-' : '',
    i = parseInt(n = Math.abs(n).toFixed(c)) + '',
    j = ((j = i.length) > 3) ? j % 3 : 0;
    return sign + (j ? i.substr(0, j) + t : '') + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : '');
  };

  var areaToParams = function (area, params) {
    params = params || {};
    $("#" + area).find('input, select').each(function(){
      params[ $(this).attr('name') ] = $(this).val();
    });
  };

  var startsWith = function(data, input) {
    return data.substring(0, input.length) === input;
  }


  var showLoading = function(text) {

    jQuery.colorbox({
      html: "<h1>Aguarde...</h1>",
      transition: 'none',
      close: false,
      escKey: false,
      overlayClose: false,
      fixed: true
    });

  };

  var showTransactionCode = function(code) {

    var html = ('<h1 class="success">'+code+'</h1>');

    jQuery.colorbox({
      html: html,
      close: false,
      escKey: false,
      overlayClose: false,
      fixed: true
    });

  };

  var showWaitingPayment = function(paymentName) {

    var html = ('<h1 class="success">'+paymentName+'</h1>');

    jQuery.colorbox({
      html: html,
      close: false,
      escKey: false,
      overlayClose: false,
      fixed: true
    });

  };

  var showPaymentErrors = function(errors) {

    if (typeof errors == 'object') {

      var errors = errors.error;
      var html = '<ul class="errors">';

      for (var i in errors) {
        var error = errors[i];
        html += ('<li>' + error.message + '</li>');
      }

      html += ('</ul>');

      jQuery.colorbox({
        html: html,
        fixed: true
      });

    }

  };

  var showCardTokenErrors = function(errors) {

    if (typeof errors == 'object') {
console.log(errors);
      var html = '<ul class="errors">';

      for (i in errors) {
        message = pagSeguroGetErrorMessage(i);
        console.log("hi" + message);
        if (!message) {
          message = errors[i];
        }

        html += ('<li>' + message + '</li>');
      }

      html += ('</ul>');

      jQuery.colorbox({
        html: html,
        fixed: true
      });

    }

  };

  var hideMessages = function() {
    jQuery.colorbox.close();
  };

  var pagSeguroGetErrorMessage = function(code) {
    message = "";
    switch (code) {
      case "30400":
        message = "Este cartão de crédito é inválido, digite novamente.";
        break;
      case "10001":
        message = "Número de digitos no cartão inválido, digite novamente.";
        break;
      case "10006":
        message = "Número de digitos no código de segurança inválido, digite novamente."
        break;
    }
    return message;
  }
