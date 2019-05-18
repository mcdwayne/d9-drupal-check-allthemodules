(function ($) {

  'use strict';

  /**
   * Behavior for the flyout menu toggle.
   */
  Drupal.behaviors.flyout_menu_toggle = {
    attach: function (context, settings) {
      $('button.flyout-menu-toggle').click(function(e) {
        e.stopImmediatePropagation();
        $(this).toggleClass('is-open');
        $('body').toggleClass('flyout-menu-open');
      });
    }
  };

  /**
   * Behavior for the flyout menu.
   */
  Drupal.behaviors.flyout_menu = {
    attach: function (context, settings) {
      resize(settings.flyout_menu.breakpoint);

      $('.sub-menu-toggle').click(function(e) {
        e.stopImmediatePropagation();
        $(this).parent().toggleClass('sub-menu-open');
      });

      $(window).resize(function () {
        resize(settings.flyout_menu.breakpoint);
      });
    }
  };

  function resize(breakpoint) {
    var mq = window.matchMedia(breakpoint);

    if (mq.matches) {
      $('body').removeClass('flyout-menu-mobile');
      $('ul.flyout-menu').removeClass('flyout-menu-mobile');
    }
    else {
      $('body').addClass('flyout-menu-mobile');
      $('ul.flyout-menu').addClass('flyout-menu-mobile');
    }
  }

})(jQuery);
