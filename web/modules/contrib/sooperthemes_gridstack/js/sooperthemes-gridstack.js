/**
 * @file
 * Gridstack init script.
 */

(function ($) {
  Drupal.behaviors.initGridstack = {
    attach: function (context, settings) {
      var gridstackSelector = '.sooperthemes-gridstack-gridstack-live';
      if ($(document).find(gridstackSelector + ' .grid-stack').length <= 0) {
        return false;
      }
      var options = {
        'verticalMargin': 0,
        'staticGrid': true
      };
      $(gridstackSelector + ' .grid-stack').gridstack(options);
    }
  };
})(jQuery);
