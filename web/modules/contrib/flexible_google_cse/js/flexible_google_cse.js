/**
 * @file
 * Get Response from Google Search 
 */

(function ($) {

  //'use strict';

  Drupal.behaviors.flexible_google_cse = {
    attach: function (context, settings) {
    //get the google key from our config . This code is copied Directly from Google
    var cx = settings.flexible_google_cse.gse_key;
    var gcse = document.createElement('script');
    gcse.type = 'text/javascript';
    gcse.async = true;
    gcse.src = 'https://cse.google.com/cse.js?cx=' + cx;
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(gcse, s);
    }
  };

})(jQuery);
