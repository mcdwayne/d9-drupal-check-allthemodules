/**
 * @file
 * handle form submission. open up the new page in a new tab, this will prevent
 * the user from leaving the site.
 */
(function ($) {

Drupal.behaviors.govDeliverySignup = {
  'attach': function() {
    $('form.govdelivery-signup')
    .addClass('gdsf-processed')
    .submit(function(event) {
      if ($('form#govdelivery-signup #edit-email').val() !== '') {
        var email = $('form#govdelivery-signup #edit-email').val();
        var url = drupalSettings.govDeliverySignup.url + 'email=' + encodeURIComponent(email);
        event.preventDefault();
        window.open(url, '_blank');
      }
    });
  },
};

})(jQuery);
