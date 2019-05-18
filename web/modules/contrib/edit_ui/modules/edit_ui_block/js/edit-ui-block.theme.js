/**
 * @file
 * Provides theme functions for all of Edit UI's client-side HTML.
 */

(function (Drupal) {
  "use strict";

  /**
   * Ghost block template.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockGhostBlock = function () {
    return '<div class="edit-ui__ghost"></div>';
  };

  /**
   * Disabled block template.
   *
   * @param Object settings
   *   An object containing the block attributes.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockDisabledBlock = function (settings) {
    return '<a href="#" class="edit-ui__toolbar__link">' + settings.label + '</a>';
  };

  /**
   * Element defining a containing box for the placement of the tooltip.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockTooltipFence = function (settings) {
    return '<div class="edit-ui__tooltip-fence"></div>';
  };

})(Drupal);
