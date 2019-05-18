/**
 * @file
 *
 * Drupal behavior for the edit_ui filters.
 */

(function (Drupal, debounce, $) {
  'use strict';

  var FILTER_MIN_LENGTH = 2;

  /**
   * Namespace for edit_ui related functionality.
   *
   * @namespace
   */
  Drupal.feadmin = Drupal.feadmin || {};

  /**
   * Drupal edit_ui filter behavior.
   */
  Drupal.behaviors.feadminFilter = {
    attach: function (context) {
      $('.feadmin_block-block-filter', context)
        .once('feadmin_block-filter')
        .each(function () {
          new Drupal.feadmin.filter(this);
        });
    }
  };

  /**
   * edit_ui filter constructor.
   * @param element
   *   The input filter element.
   */
  Drupal.feadmin.filter = function (element) {
    this.$input = $(element);
    this.$elements = $('#feadmin-toolbar .feadmin_block-block');
    this.$input.on({
      keyup: debounce(this.filter.bind(this), 200),
      keydown: preventEnterKey
    });
  };

  /**
   * Display or hide elements depending on user input.
   */
  Drupal.feadmin.filter.prototype.filter = function () {
    var $matchingElements = this.$elements.filter(this.match.bind(this));
    $matchingElements.show();
    this.$elements.not($matchingElements).hide();
  };

  /**
   * Test matching query.
   *
   * @param index
   *   The index of the matching element for filering methid.
   * @return {boolean}
   *   Match or not
   */
  Drupal.feadmin.filter.prototype.match = function (index) {
    var query = this.$input.val().toLowerCase();
    var text = this.$elements.eq(index).text().toLowerCase();
    return (query.length < FILTER_MIN_LENGTH || text.indexOf(query) !== -1);
  };

  function preventEnterKey(event) {
    if (event.which === 13) {
      event.preventDefault();
      event.stopPropagation();
    }
  }

})(Drupal, Drupal.debounce, jQuery);
