/**
 * @file
 * Provides theme functions for the loading animation client side.
 */

(function (Drupal) {

  'use strict';

  /**
   * Theme function for the loading animation.
   *
   * @returns {string}
   *   The corresponging HTML.
   */
  Drupal.theme.loadingAnimationLoader = function() {
    var html = '';
    html += '<div class="loading-animation">';
    html += '<div class="loading-animation__box">';
    html += '<div class="loading-animation__outer">';
    html += '<div class="loading-animation__inner">&nbsp;</div>';
    html += '</div>';
    html += '<span class="loading-text">';
    html +=  Drupal.t('Loading ...', {}, {context: "loading_animation"});
    html += '</span>';
    html += '</div>';
    html += '</div>';

    return html;
  };

})(Drupal);
