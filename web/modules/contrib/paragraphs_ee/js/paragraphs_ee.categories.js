(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Filter items in dialog by a given search string.
   *
   * @param object $dialog
   *   The dialog to filter items.
   * @param string search
   *   The string to search for.
   * @param bool search_description
   *   If <code>true</code> the items description will be searched also.
   */
  var filterItems = function ($dialog, search, search_description) {
    if ('' === search) {
      // Display all potentially hidden elements.
      $('.button-group', $dialog).removeClass('js-hide');
      $('.paragraphs-add-dialog-row', $dialog).removeClass('js-hide');
      return;
    }
    // Hide rows matching the input.
    $('.paragraphs-add-dialog-row', $dialog).each(function () {
      var $row = $(this);
      var input_found = $('.paragraphs-label', $row).html().toLowerCase().indexOf(search.toLowerCase()) !== -1;
      var description = $('.paragraphs-description', $row).html() || '';
      if (search_description) {
        input_found |= (description.toLowerCase().indexOf(search.toLowerCase()) !== -1);
      }
      if (input_found) {
        $row.removeClass('js-hide');
      }
      else {
        $row.addClass('js-hide');
      }
    });
    // Hide categories if no rows are visible.
    $('.button-group', $dialog).each(function () {
      var $group = $(this);
      if ($('.paragraphs-add-dialog-row.js-hide', $group).length === $('.paragraphs-add-dialog-row', $group).length) {
        $group.addClass('js-hide');
      }
      else {
        $group.removeClass('js-hide');
      }
    });
  };

  /**
   * Init display toggle for listing in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogDisplayToggle = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        var $dialog = $(this);
        var $toggle = $('.display-toggle', $dialog);
        $toggle.once().on('click', function () {
          var $self = $(this);

          if ($self.hasClass('style-list')) {
            $dialog.addClass('paragraphs-style-list');
          }
          else {
            $dialog.removeClass('paragraphs-style-list');
          }
        });
      });
    }
  };

  /**
   * Init filter for paragraphs in paragraphs modal.
   */
  Drupal.behaviors.initParagraphsEEDialogFilter = {
    attach: function (context) {
      $('.paragraphs-add-dialog--categorized', context).each(function () {
        var $dialog = $(this);
        if ($('.paragraphs-add-dialog-row', $dialog).length < 3) {
          // We do not need to enable the filter for very few items.
          return;
        }

        var $filter_wrapper = $('.filter', $dialog);
        $filter_wrapper.removeClass('js-hide');

        var $filter = $('.item-filter', $filter_wrapper);
        $filter.once().on('input', function () {
          filterItems($dialog, $filter.val(), $search_description.is(':checked'));
        });
        var $search_description = $('.search-description :checkbox', $filter_wrapper);
        $search_description.once().on('change', function () {
          filterItems($dialog, $filter.val(), $search_description.is(':checked'));
        });
      });
    }
  };

}(jQuery, Drupal, drupalSettings));
