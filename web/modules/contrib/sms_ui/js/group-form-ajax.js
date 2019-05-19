/**
 * @file
 * Handles AJAX interactions for sms_ui components.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * SMS Group list update ajax command.
   *
   * @param {Drupal.Ajax} [ajax]
   *   A {@link Drupal.ajax} object.
   * @param {object} response
   *   Ajax response.
   * @param {string} response.selector
   *   Selector to use.
   * @param {string} response.values
   *   The new list to be populated.
   */
  Drupal.AjaxCommands.prototype.smsUiReloadGroupList = function (ajax, response) {
    // Reload the group-list specified by the selector.
    var $list = $(response.selector).empty();
    $.each(response.values, function(value,key) {
      $list.append($("<option></option>").attr("value", value).text(key));
    });
  };

})(jQuery, Drupal, drupalSettings);
