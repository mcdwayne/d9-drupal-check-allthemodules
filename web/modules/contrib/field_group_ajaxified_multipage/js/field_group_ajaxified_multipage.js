/**
  * @file
  * A JavaScript file for the module.
  */

(function ($, Drupal) {
  Drupal.behaviors.field_group_ajaxified_multipage = {
    attach: function (context, settings) {
      scrollToTop('.field-group-ajaxifield-multipage-scroll', context);
    }
  };

  /**
   * Scroll to top.
   */
  function scrollToTop(element, context) {
    $(element).once('scroll-to-top').each(function () {
      var $element = $(this);
      if (!$element.hasClass('prevent-scroll') || $element.find('.error').length > 0) {
        $('html, body').animate({
          scrollTop: $element.offset().top - 20
        }, 200);
      }
    });
  }
})(jQuery, Drupal);
