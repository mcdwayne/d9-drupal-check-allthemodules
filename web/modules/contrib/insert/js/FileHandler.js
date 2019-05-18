(function($, Drupal) {
  'use strict';

  var PARENT = Drupal.insert.Handler;

  /**
   * @type {Object}
   */
  var SELECTORS = {
    description: 'input[name$="[description]"]',
    filename: 'input.insert-filename'
  };

  /**
   * Builds content to be inserted on generic file fields.
   * @constructor
   *
   * @param {Drupal.insert.Inserter} inserter
   * @param {Object} [widgetSettings]
   * @param {HTMLElement} [wrapper]
   */
  Drupal.insert.FileHandler = Drupal.insert.FileHandler || (function() {

    /**
     * @constructor
     *
     * @param {Drupal.insert.Inserter} inserter
     * @param {Object} [widgetSettings]
     * @param {HTMLElement} [wrapper]
     */
    function FileHandler(inserter, widgetSettings, wrapper) {
      PARENT.prototype.constructor.apply(this, arguments);
    }

    $.extend(FileHandler.prototype, PARENT.prototype, {
      _selectors: SELECTORS
    });

    return FileHandler;

  })();

})(jQuery, Drupal);
