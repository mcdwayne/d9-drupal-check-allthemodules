(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    function loginAdback(e) {
        $('#ab-login-adback').prop('disabled', true);
        var callback = encodeURI(e.currentTarget.dataset.callbackUrl);
        window.location.href = 'https://www.adback.co/tokenoauth/site?redirect_url=' + callback;
    }

    function registerAdback(event) {
        $('#ab-register-adback').prop('disabled', true);
        var callback = encodeURI(event.currentTarget.dataset.callbackUrl);
        window.location.href = 'https://www.adback.co/en/register/?redirect_url='
            + callback
            + '&email=' + $(event.target).data('email')
            + '&website=' + $(event.target).data('site-url');
    }


    function _logout(event) {
        var destination = event.currentTarget.getAttribute('href');
        window.location.href = destination;
    }

    $(document).ready(function () {
        // Alert
        if(typeof vex === 'object') {
            vex.defaultOptions.className = 'vex-theme-default';
        }

        $("#ab-logout").on('click', _logout);

        if ($("#ab-login").length > 0) {
            $("#ab-login-adback").on('click', loginAdback);
            $("#ab-register-adback").on('click', registerAdback);


            $("#ab-username,#ab-password").on('keyup', function (e) {
                var code = e.which; // recommended to use e.which, it's normalized across browsers
                if (code == 13) {
                    e.preventDefault();
                    loginAdback(e);
                }
            });
        }

        if ($("#ab-website").length > 0) {
            $("#ab-website").on('click', function (event) {
                var locale = $(event.target).data('locale');
                var email = $(event.target).data('email');
                window.location.href = 'https://www.adback.co/'+locale+'/login?_login_email='+email;
            });
        };

        $(".adback-incentive").on('click', function () {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'dismiss_adback_incentive'
                }
            })
        })

    });


})(jQuery);
