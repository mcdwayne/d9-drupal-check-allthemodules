(function ($) {'use strict';

Drupal.behaviors.lockr_move_to_prod = {
    attach: function (ctx, settings) {
        $('.move-to-prod-submit').hide();
        $('.move-to-prod').once('lockr-move-to-prod').click(function (e) {
            e.preventDefault();
            var url = settings.lockr.accounts_host +
                '/move-to-prod?lockr_keyring=' + settings.lockr.keyring_id;
            var popup = window.open(url, 'LockrMoveToProd', 'toolbar=off,height=850,width=650');
            window.addEventListener('message', function (e) {
                var client_token = e.data.client_token;
                popup.close();
                $('[name="client_token"]').val(client_token);
                $('.move-to-prod-submit').click();
            }, false);
        });
    },
};

}(jQuery));
