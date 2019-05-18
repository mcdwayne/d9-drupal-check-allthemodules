(function ($, Drupal, settings) {
    Drupal.behaviors.paypalinsights_beg = {
        attach: function (context) {
            (function (a, t, o, m, s) {
                a[m] = a[m] || [];
                a[m].push({t: new Date().getTime(), event: 'snippetRun'});
                var f = t.getElementsByTagName(o)[0], e = t.createElement(o), d = m !== 'paypalDDL' ? ' &m=' + m : '';
                e.async = !0;
                e.src = 'https://www.paypal.com/tagmanager/pptm.js?id=' + s + d;
                f.parentNode.insertBefore(e, f);
            })(window, document, 'script', 'paypalDDL', settings.paypal_marketing_solutions.cid);
        }
    };
})(jQuery, Drupal, drupalSettings);
