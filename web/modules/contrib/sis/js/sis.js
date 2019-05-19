function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

/**
 * @file
 * Smart Imaging Service behavior.
 */

(function (document, $, Drupal) {

  var sisEnabledNodes = [];

  /**
   * Update (sizes) attribute(s) of the SIS enabled nodes.
   */
  function updateSisEnabledNodes() {
    var _iteratorNormalCompletion = true;
    var _didIteratorError = false;
    var _iteratorError = undefined;

    try {
      for (var _iterator = sisEnabledNodes[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
        var node = _step.value;

        var pictureNode = node.closest('picture');
        var data = JSON.parse(node.dataset.sis);
        var parentElement = pictureNode ? pictureNode.parentElement : node.parentElement;

        // Set the new width for the sizes attribute.
        // Maximize the width to the widest image to prevent up scaling.
        var sizesWidth = (parentElement.clientWidth < data.maxImageWidth ? parentElement.clientWidth : data.maxImageWidth) + 'px';

        // @todo Allow lazy loading.
        node.setAttribute('sizes', sizesWidth);

        // Stretch the low resolution image until the high resolution image
        // is loaded.
        // @todo make this a setting (and restore image ratio).
        if (pictureNode) {
          pictureNode.setAttribute('width', sizesWidth);
          return;
        }
        node.setAttribute('width', sizesWidth);
      }
    } catch (err) {
      _didIteratorError = true;
      _iteratorError = err;
    } finally {
      try {
        if (!_iteratorNormalCompletion && _iterator.return) {
          _iterator.return();
        }
      } finally {
        if (_didIteratorError) {
          throw _iteratorError;
        }
      }
    }
  }

  $(document).on('drupalViewportOffsetChange.sis', updateSisEnabledNodes);

  /**
   * Transforms responsive image styles into Smart Imaging Styles.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches SIS behaviour to SIS enabled responsive images.
   */
  Drupal.behaviors.sis = {
    attach: function attach(context) {
      sisEnabledNodes = [].concat(_toConsumableArray(sisEnabledNodes), _toConsumableArray(context.querySelectorAll('[data-sis]')));

      $(document).trigger('drupalViewportOffsetChange.sis');
    }

};
})(document, jQuery, Drupal);
