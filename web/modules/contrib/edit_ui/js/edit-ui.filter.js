/**
 * @file
 *
 * Drupal behavior for the edit_ui filters.
 */

(function (Drupal, $) {
  "use strict";

  var FILTER_MIN_LENGTH = 2;

  /**
   * Namespace for edit_ui related functionality.
   *
   * @namespace
   */
  Drupal.editUi = Drupal.editUi || {};

  /**
   * Drupal edit_ui filter behavior.
   */
  Drupal.behaviors.editUiFilter = {
    attach: function (context, settings) {
      $('.js-edit-ui__filter')
        .once('edit-ui-filter')
        .each(function () {
          new Drupal.editUi.filter(this);
        });
    }
  };

  /**
   * edit_ui filter constructor.
   */
  Drupal.editUi.filter = function (element) {
    this.$input = $(element);
    this.$elements = $(this.$input.data('element'));
    this.$input.on('keyup.editUiFilter', this.filter.bind(this));
  };

  /**
   * Display or hide elements depending on user input.
   */
  Drupal.editUi.filter.prototype.filter = function () {
    var $matchingElements = this.$elements.filter(this.match.bind(this));
    $matchingElements.show();
    this.$elements.not($matchingElements).hide();
  };

  /**
   * Test matching query.
   *
   * @return {boolean}
   *   Match or not
   */
  Drupal.editUi.filter.prototype.match = function (index) {
    var query = this.$input.val().toLowerCase();
    var text = this.$elements.eq(index).text().toLowerCase();
    return (query.length < FILTER_MIN_LENGTH || text.indexOf(query) !== -1);
  };

})(Drupal, jQuery);
