var utag_data = drupalSettings.tealium.tealium.utagData;

(function(a,b,c,d){
    a='//tags.tiqcdn.com/utag/' +
        drupalSettings.tealium.tealium.account + '/' +
        drupalSettings.tealium.tealium.profile + '/' +
        drupalSettings.tealium.tealium.environment + '/utag.js';
    b=document;
    c='script';
    d=b.createElement(c);
    d.src=a;
    d.type='text/java'+c;
    d.async=drupalSettings.tealium.tealium.async;
    a=b.getElementsByTagName(c)[0];
    a.parentNode.insertBefore(d,a);
})();

(function ($, u) {
    'use strict';
    Drupal.behaviors.tealium = {
        attach: function(context, settings) {
            var utag_link = drupalSettings.tealium.tealium.utagLink
            if (utag_link.length > 0) {
                u.track("link", utag_link);
            }

            var utag_view = drupalSettings.tealium.tealium.utagView;
            if (utag_view.length > 0){
                u.track("view", utag_view);
            }
        }
    }
}(jQuery, utag_data));
