/**
 * @file
 * Helper JS for the node view page.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.entityCss = {
    attach: function (context) {
      if (typeof drupalSettings.entityCss.background !== 'undefined') {
        console.log(drupalSettings.entityCss.pageSelector + '.background=' + drupalSettings.entityCss.background)
        $(drupalSettings.entityCss.pageSelector).css('background', drupalSettings.entityCss.background);
      }
      if (typeof drupalSettings.entityCss.textColor !== 'undefined') {
        console.log(drupalSettings.entityCss.pageSelector + '.textColor=' + drupalSettings.entityCss.textColor)
        $(drupalSettings.entityCss.pageSelector).css('color', drupalSettings.entityCss.textColor);
      }
    }
  };

})(jQuery);
