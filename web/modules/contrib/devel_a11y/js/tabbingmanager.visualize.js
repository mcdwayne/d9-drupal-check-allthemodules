/**
 * @file
 * Visualized page tabbing modifications made by Drupal.TabbingManager.
 */

(function ($, Drupal) {

$(document).on('drupalTabbingContextActivated.devel_a11y', function (event, tabbingContext) {
  tabbingContext.$tabbableElements.addClass('tabbingmanager-visualize-tabbable');
});

$(document).on('drupalTabbingContextDeactivated.devel_a11y', function (event, tabbingContext) {
  tabbingContext.$tabbableElements.removeClass('tabbingmanager-visualize-tabbable');
});

}(jQuery, Drupal));
