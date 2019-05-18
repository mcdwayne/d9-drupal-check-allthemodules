/**
 * @file
 * CCK Select Other Javascript Behaviors
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.cckSelectOther = {
    /**
     * Bind to each cck select other field widget delta select element.
     */
    attach: function () {
      var n, i,
          list_element = '';

      // Prevent errors
      if (typeof drupalSettings.CCKSelectOther !== 'object')
        return;

      // Assume that JQuery 1.9 works with MSIE because they removed the
      // .browser functionality. JQueryWTF.
      for (n in drupalSettings.CCKSelectOther) {
        for (i in drupalSettings.CCKSelectOther[n]) {
          list_element = $('.' + drupalSettings.CCKSelectOther[n][i] + ' select');
          $(list_element).bind('click', {element: list_element}, this.toggle).trigger('click');
        }
      }
    },
    /**
     * Look through selected options of select list, and toggle the display
     * based on whether or not other is selected.
     */
    toggle: function (e) {
      var input_element = $(this).parent().next();
      var selected_other = 'none';

      $(this).children(':selected').each(function() {
          selected_other = ($(this).val() === 'other') ? 'block' : 'none';
      });

      $(input_element).css('display', selected_other);
    }
  };
})(jQuery, Drupal, drupalSettings);
