alert('hi');
(function ($, drupalSettings) {
    Drupal.behaviors.user_lock = {
      attach: function (context) {
        console.log(drupalSettings.message);
      }
    };
})(jQuery, drupalSettings);
