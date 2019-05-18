/**
 * @file
 * RWD menu functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.rwd_menu = {
    attach: function (context, settings) {
      $(context).find('.rwd-menu').once('rwd_menu_init').each(function () {
        var menu = $(this);
        // Add menu overlay to body.
        menu.append('<a href="#" class="rwd-menu-trigger overlay"></a>')

        // Trigger menu.
        $('.rwd-menu-trigger').once('rwd_menu_init').click(function (event) {
          event.preventDefault();
          if (menu.hasClass('active')) {
            menu.removeClass('active').removeClass('rwd-menu-opened');
            menu.find('.menu').removeClass('active');
          }
          else {
            menu.addClass('active').addClass('rwd-menu-opened');
            menu.find('.level-0').addClass('active').addClass('current');
          }
        });

        // Expand branch.
        menu.find('.branch > a').click(function (event) {
          event.preventDefault();
          $(this).parents('div.menu.current').removeClass('current').scrollTop(0);
          $(this).next().addClass('active').addClass('current');
        });

        // Close branch.
        menu.find('.back').click(function (event) {
          event.preventDefault();
          $(this).parents('div.menu.current').removeClass('current').removeClass('active').scrollTop(0);
          $(this).parents('div.menu.active').first().addClass('current');
        });
      });
    }
  };
})(jQuery, Drupal);
