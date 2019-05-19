/**
 * @file
 * Webform select collection behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.webformSelectCollection = {
    attach: function (context, settings) {

      $(context).find('th.select-all-collection').closest('table').once('table-select').each(Drupal.tableSelectCollection);

    }
  };


  Drupal.tableSelectCollection = function () {
    if ($(this).find('td.webform-select-collection-select select').length === 0) {
      return;
    }

    var $table = $(this);
    var $select_boxes = $table.find('td.webform-select-collection-select select:enabled');
    var selectAllTitle = Drupal.t('Select value for all rows in this table');
    var $selectAll = $select_boxes.first().parents('.form-item').clone();

    $selectAll.find('select').attr('title', selectAllTitle);
    // Select all element should never be required.
    $selectAll.find('select')
      .removeAttr('required')
      .removeAttr('aria-required')
      .removeClass('required');
    $table.find('th.select-all-collection').prepend($selectAll);

    var resetSelectAll = function resetSelectAll() {
      var $select = $table.find('th.select-all-collection select');
      var default_value = $select.find('option').first().attr('value');

      $select.val(default_value);
    };

    $selectAll.on('change', function (event) {

      var target_value = $(event.target).val();

      $select_boxes.each(function () {
        var $select_box = $(this);
        var select_value = $select_box.val();

        var stateChanged = select_value !== target_value;

        if (stateChanged) {
          $select_box.val(target_value).trigger('change');
        }
      });
    });

    $select_boxes.on('change', function () {
      if ($selectAll.find('select').val() !== $(this).val()) {
        resetSelectAll();
      }
    });

  };

} (jQuery, Drupal));
