/**
 * @file
 * Log page tabbing modifications made by Drupal.TabbingManager.
 */

(function ($, Drupal, console) {

$(document).on('drupalTabbingContextActivated.devel_a11y', function (event, tabbingContext) {
  console.info('TabbingManager: tabbing contraint activated, level %s, %d tabbable elements, %d disabled elements.', tabbingContext.level, tabbingContext.$tabbableElements.length, tabbingContext.$disabledElements.length);
});

$(document).on('drupalTabbingContextDeactivated.devel_a11y', function (event, tabbingContext) {
  console.info('TabbingManager: tabbing contraint deactivated, level %s.', tabbingContext.level);
});

}(jQuery, Drupal, window.console));
