/**
 * @file
 * Implements javascript for the module.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.insertCaptcha = {
    attach: function (context) {
      $('.instant-solr-index-get-captcha-form',context).hide();
      $('.submitRecaptcha', context).hide();
      $('.add-search-env',context).click(function (e){
        e.preventDefault();
        $('.instant-solr-index-get-captcha-form',context).show();
      });
    }
  };
})(jQuery);

/**
 * Callback function for recaptcha from google.
 */
function recaptcha_verify_callback() {
  'use strict';
  jQuery('.captcha_wrapper').after(Drupal.t('Please wait the server is being created.....'));
  jQuery('.submitRecaptcha').trigger('click');
}
