/**
 * @file
 * Contains simple_megamenu.js.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.simple_megamenu = {
    attach: function(context, settings) {

      // Basic a11y.
      // Make the megamenu navigable with keyboard.
      $('ul.menu--simple-mega-menu li.menu-item--expanded a').on('focusin', function(){
        $('li.menu-item--expanded').removeClass('focused');
        $(this).parents('li.menu-item--expanded').addClass('focused');
      });

      // Close menu on click anywhere.
      $('body').on('click', function(){
        $('li.menu-item--expanded').removeClass('focused');
      });

      // Close menu on escape key press.
      $(document).keyup(function(e) {
        if (e.keyCode == 27) {
          $('li.menu-item--expanded').removeClass('focused');
        }
      });
    }
  };

}(jQuery, Drupal));
