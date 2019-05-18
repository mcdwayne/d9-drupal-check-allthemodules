/**
 * @file
 * Attaches the behaviors for the content scroller module.
 */

(function ($, Drupal) {
  Drupal.behaviors.mScroller = {
    attach: function (context, settings) {
      if ((typeof settings.content_scroller_setting != 'undefined')) {
        var selector = settings.content_scroller_setting.content_scroller_selector;
        var scroll_type = settings.content_scroller_setting.content_scroller_type;
        var scroll_theme = settings.content_scroller_setting.content_scroller_theme;
        if (typeof selector != 'undefined') {
          $(selector).mCustomScrollbar({
            axis: 'scroll_type',
            theme: 'scroll_theme',
            scrollbarPosition: 'inside'
          });
        }
      }
    }
  };
})(jQuery, Drupal);
