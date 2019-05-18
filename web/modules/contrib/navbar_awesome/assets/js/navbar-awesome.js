/**
 * @file
 * Defines the behavior of the Drupal administration navbar.
 */

(function ($, Backbone, Drupal) {
  'use strict';

  Drupal.behaviors.navbarAwesome = {
    attach: function (context, settings) {
      var verticalIcon = 'fa-chevron-up';
      var horizontalIcon = 'fa-chevron-left';

      if (settings.hasOwnProperty('navbarAwesome')) {
        verticalIcon = settings.navbarAwesome.vertical || verticalIcon;
        horizontalIcon = settings.navbarAwesome.horizontal || horizontalIcon;
      }

      $('.toolbar-icon-toggle-vertical', context).toggleClass(horizontalIcon);
      $('.toolbar-icon-toggle-horizontal', context).toggleClass(verticalIcon);

      $(document).on('drupalToolbarOrientationChange', function (e, orientation) {
        var orientationButton = $('#toolbar-administration .toolbar-toggle-orientation button', context);
          // Add fa class.
        $(orientationButton)
          .addClass('fa')
          .toggleClass(verticalIcon, (orientation === 'vertical'))
          .toggleClass(horizontalIcon, (orientation === 'horizontal'));
      });
    }
  };

}(jQuery, Backbone, Drupal));
