(function ($, Drupal, window) {
  Drupal.behaviors.entityBSTabFormatterResponsive = {
    attach: function (context, settings) {
      $(document).ready(function() {
        $('.nav-tabs.responsive', context).each(function() {
          fakewaffle.responsiveTabs(['xs', 'sm']);
        });
      });
    }
  };
})(jQuery, Drupal, window);
