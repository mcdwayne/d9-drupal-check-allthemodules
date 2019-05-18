(function ($) {'use strict';

Drupal.behaviors.lockr_register = {
  attach: function (ctx, settings) {
    $('.register-submit').hide();
    $('.register-site').once('lockr_register').click(function (e) {
      e.preventDefault();
      var url = settings.lockr.accounts_host + '/register-keyring';
      if (settings.lockr.site_name) {
        var site_name = encodeURIComponent(settings.lockr.site_name)
          .replace(/%20/g, '+');
        url += '?keyring_label=' + site_name;
      }
      var popup = window.open(url, 'LockrRegister', 'toolbar=off,height=850,width=650');
      window.addEventListener('message', function (e) {
        var client_token = e.data.client_token;
        popup.close();
        $('[name="client_token"]').val(client_token);
        $('.register-submit').click();
      }, false);
    });
  },
};

}(jQuery));
