/**
 * @file
 * JS for edgy panels.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.paragraphCss = {
    attach: function (context) {
      // If any of the panels are edgy, then re-order the DOM and classes to
      // support edge-to-edge backgrounds. This is mainly done by removing the
      // container class from the top level, and re-applying it to the rows
      // that are not edge-to-edge. This way, the row that is edge-to-edge will
      // not have a container. Note that bootstrap container does not flow
      // properly when on rows, so container is changed to container-fluid.
      var edgy = $('.edgy');
      if (edgy.length > 0) {
        // Replace the container with container-fluid.
        $('.container').removeClass('.container').addClass('container-fluid');

        // Remove container-fluid from edgy ancestors.
        var container = $(edgy).closest('.container-fluid');
        $(container).removeClass('container-fluid');

        // Add container-fluid to the regions. Above we remove it from the
        // top level ancestors. Here we add it to the regions.
        $(container).find('.bs-region').addClass('container-fluid');

        // Add container-fluid to edgy tabs.
        $(container).find('nav.tabs').addClass('container-fluid');

        // Remove container-fluid from the edgy region.
        $(edgy).closest('.bs-region').removeClass('container-fluid');

        // Organize all of the regions by row.
        var regions = $('.bs-region');
        var rows = [], rowName = '';
        for (var regionDelta = 0; regionDelta < regions.length; regionDelta++) {
          rowName = Drupal.behaviors.paragraphCss.getRowName(regions[regionDelta], 'bs-region--row');
          if (rowName !== '') {
            // Add this region's class to the array per row name.
            if (typeof rows[rowName] === 'undefined') {
              rows[rowName] = [];
            }
            rows[rowName].push(regions[regionDelta]);
          }
        }

        // Create a parent div to hold all regions where at least one of the
        // regions is edgy.
        for (var edgyDelta = 0; edgyDelta < edgy.length; edgyDelta++) {
          // Get the edgy regions row. This should never be empty.
          rowName = Drupal.behaviors.paragraphCss.getRowName(edgy[edgyDelta], 'block-region-row');
          if (rows[rowName] === null) {
            // Skip if this has already been done. This can happen if two
            // columns in the same row are both marked as edgy.
            continue;
          }

          // Add all columns in this edgy row to a new div.
          for (var colDelta = 0; colDelta < rows[rowName].length; colDelta++) {
            // Get the region.
            var region = $(rows[rowName][colDelta]);

            // Create a new div after the first region in the row.
            var regionClass = 'bs-region--' + rowName;
            var newDivs = $('.' + regionClass), newDiv;
            if (newDivs.length > 0) {
              newDiv = newDivs[0];
            }
            else {
              newDiv = $('<div>', {
                class: 'clearfix edgy col-sm-12 bs-region ' + regionClass
              });
              region.after(newDiv);
            }

            // Move this edgy region inside the new div.
            region.appendTo(newDiv);
          }

          // Mark this row as done, so that if two columns in the same row are
          // both marked edgy, that this is only done once.
          rows[rowName] = null;
        }
      }
    },

    /**
     * Return the elements rowName.
     *
     * @param object element
     *   The DOM element.
     * @param string classPrefix
     *   The class prefix that ends in "row", used to keep the row number.
     *
     * @returns string
     *   Returns the elements rowName.
     */
    getRowName: function (element, classPrefix) {
      var classPrefixLength = classPrefix.length;
      if (classPrefix.substring(classPrefixLength - 3) !== 'row') {
        // The class prefix must end in "row".
        return '';
      }

      // Loop through the classes, looking for the class prefix.
      var regionClass = $(element).attr('class').split(' ');
      var className = '', classDelta = 0;
      for (classDelta = 0; classDelta < regionClass.length; classDelta++) {
        className = regionClass[classDelta];
        if (className.substring(0, classPrefixLength) === classPrefix) {
          // Get the row name as "row1", so start after "bs-region--" and
          // remove everything from the following dash onward..
          var rowName = className.substring(classPrefixLength - 3);
          var dashPos = rowName.indexOf('-');
          if (dashPos !== -1) {
            rowName = rowName.substring(0, dashPos);
          }
          return rowName;
        }
      }

      return '';
    }

  };

})(jQuery);
