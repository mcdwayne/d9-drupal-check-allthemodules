(function ($, Drupal, drupalSettings, FreshWidget) {

  'use strict';

  Drupal.behaviors.diffRevisions = {
    attach: function (context, settings) {
      FreshWidget.init("", settings.freshdesk);
    }
  };

})(jQuery, Drupal, drupalSettings, FreshWidget);
