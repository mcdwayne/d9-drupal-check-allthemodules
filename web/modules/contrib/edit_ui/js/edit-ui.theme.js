/**
 * @file
 * Provides theme functions for all of Edit UI's client-side HTML.
 */

(function (Drupal) {
  "use strict";

  /**
   * Wrapper block template.
   *
   * @param Object settings
   *   An object containing the block attributes.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockWrapperBlock = function (settings) {
    return '<div id="edit-ui-' + settings.id + '" class="clearfix edit-ui__block"></div>';
  };

  /**
   * Placeholder block template.
   *
   * @param Object settings
   *   An object containing the block attributes.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockPlaceholderBlock = function (settings) {
    return '' +
      '<div id="block-' + settings.id + '" class="block block-' + settings.provider + ' js-edit-ui__block__' + settings.plugin_id + '">' +
        '<h2>' + settings.label + '</h2>' +
      '</div>';
  };

  /**
   * Tooltip template.
   *
   * @param Object settings
   *   An object containing the block attributes.
   *
   * @return String
   *   A string representing a DOM fragment.
   */
  Drupal.theme.editUiBlockTooltip = function (settings) {
    var label = settings.label + ' (' + settings.id + ')';
    return '' +
      '<div class="edit-ui__tooltip js-edit-ui__tooltip clearfix">' +
        '<i class="edit-ui__tooltip__pointer"></i>' +
        '<div class="edit-ui__tooltip__content">' +
          '<div class="edit-ui__tooltip__label" title="' + label + '">' + label + '</div>' +
        '</div>' +
        '<div class="edit-ui__tooltip__lining"></div>' +
      '</div>';
  };

})(Drupal);
