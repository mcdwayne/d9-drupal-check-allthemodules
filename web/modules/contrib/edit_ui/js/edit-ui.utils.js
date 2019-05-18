/**
 * @file
 * Drupal behavior for the edit_ui blocks.
 */

(function (Drupal, drupalSettings) {
  "use strict";

  /**
   * edit_ui utils functions.
   */
  Drupal.editUi = Drupal.editUi || {};
  Drupal.editUi.utils = {

    /**
     * Get mouse or touch position.
     *
     * @param Event event
     *   The event object.
     */
    getPosition: function (event) {
      var position = {x: 0, y: 0};

      if (event.type.indexOf('touch') === 0) {
        position = {
          x: event.originalEvent.changedTouches[0].pageX,
          y: event.originalEvent.changedTouches[0].pageY
        };
      }
      else {
        position = {
          x: event.pageX,
          y: event.pageY
        };
      }

      return position;
    },

    /**
     * Get which mouse button was triggered in a mouse event.
     * Inspired by https://github.com/bevacqua/dragula.
     *
     * @param event
     *   The event object.
     * @return number
     *   The pressed mouse button.
     */
    whichMouseButton: function (event) {
      var button;
      if (event.originalEvent) {
        event = event.originalEvent;
      }
      if (event.touches !== void 0) {
        return event.touches.length;
      }
      if (event.which !== void 0 && event.which !== 0) {
        return event.which;
      }
      if (event.buttons !== void 0) {
        return event.buttons;
      }
      button = event.button;
      if (button !== void 0) {
        if (button & 1) {
          return 1;
        }
        else if (button & 2) {
          return 3;
        }
        else if (button & 4) {
          return 2;
        }
        else {
          return 0;
        }
      }
    },

    /**
     * Is the given element an input or editable element.
     * Inspired by https://github.com/bevacqua/dragula.
     *
     * @param element
     *   The element to be checked.
     * @return boolean
     *   Editable or not.
     */
    isInput: function (element) {
      return element.tagName === 'INPUT' ||
        element.tagName === 'TEXTAREA' ||
        element.tagName === 'SELECT' ||
        this.isEditable(element);
    },

    /**
     * Is the given element editable.
     * Inspired by https://github.com/bevacqua/dragula.
     *
     * @param element
     *   The element to be checked.
     * @return boolean
     *   Editable or not.
     */
    isEditable: function (element) {
      if (!element || element === document) {
        // no parents were editable.
        return false;
      }
      if (element.contentEditable === 'false') {
        // stop the lookup.
        return false;
      }
      if (element.contentEditable === 'true') {
        // found a contentEditable element in the chain.
        return true;
      }

      // contentEditable is set to 'inherit'
      return this.isEditable(element.parentNode);
    },

    /**
     * Calculates region and block dimensions.
     */
    calculateDimensions: function () {
      if (Drupal.editUi.block.collections.blockCollection) {
        // Calculate block dimensions.
        Drupal.editUi.block.collections.blockCollection.forEach(function (block) {
          block.trigger('calculateDimensions');
        });
      }

      if (Drupal.editUi.region.collections.regionCollection) {
        Drupal.editUi.region.collections.regionCollection.forEach(function (region) {
          var regionOffset;
          var regionDimensions;
          var blocks;

          // Calculate region dimensions.
          region.trigger('calculateDimensions');

          regionOffset = region.get('offset');
          regionDimensions = region.get('dimensions');
          blocks = Drupal.editUi.block.collections.blockCollection.getRegionBlocks(region.get('region'));

          // Calculate margins.
          blocks.forEach(function (block, index) {
            var prev;
            var next;
            var blockOffset = block.get('offset');
            var blockDimensions = block.get('dimensions');

            if (index === 0) {
              prev = regionOffset.top;
            }
            else {
              prev = blocks[index - 1].get('offset').top + blocks[index - 1].get('dimensions').height;
            }

            if (index === blocks.length - 1) {
              next = regionOffset.top + regionDimensions.height;
            }
            else {
              next = blocks[index + 1].get('offset').top;
            }

            block.setMargins({
              top: blockOffset.top - prev,
              bottom: next - blockOffset.top - blockDimensions.height
            });
          });
        });
      }
    },

    /**
     * Reset default states.
     */
    reset: function () {
      // Unselect and deactivate all regions.
      if (Drupal.editUi.region.collections.regionCollection) {
        Drupal.editUi.region.collections.regionCollection.forEach(function (region) {
          region.unselect();
          region.deactivate();
        });
      }

      // Show all blocks with content.
      if (Drupal.editUi.block.collections.blockCollection) {
        Drupal.editUi.block.collections.blockCollection.forEach(function (block) {
          if (block.get('block')) {
            block.show();
          }
        });
      }

      // Hide dragging block.
      if (Drupal.editUi.block.models.draggingBlockModel) {
        Drupal.editUi.block.models.draggingBlockModel.hide();
        Drupal.editUi.block.models.draggingBlockModel.trigger('update');
      }
    },

    /**
     * Get current page for block visibility.
     *
     * @returns {string}
     *   Current path.
     */
    getCurrentPath: function () {
      var path = drupalSettings.path.baseUrl + drupalSettings.path.currentPath;
      if (drupalSettings.path.isFront) {
        path = '<front>';
      }
      return path;
    }
  };

})(Drupal, drupalSettings);
