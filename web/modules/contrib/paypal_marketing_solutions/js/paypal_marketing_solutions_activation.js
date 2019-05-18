(function ($, Drupal, settings) {
    Drupal.behaviors.paypalinsights = {
        attach: function (context) {

            var muse_options = {
                onContainerCreate: callback_onsuccess,
                hn: 'www.merchant-site.com',
                partner_name: 'Shopify',
                bn_code: 'ITECHART_SHOPIFY',
                env: 'production',
                cid: settings.paypal_marketing_solutions.cid
            };

            function callback_onsuccess(containerId) {
                muse_options.cid = containerId;
                var url = settings.paypal_marketing_solutions.path + containerId;
                $.ajax({
                    method: 'GET',
                    url: url
                })
            }

            MUSEButton('muse-activate-managesettings-button', muse_options);

        }
    }
})(jQuery, Drupal, drupalSettings);
