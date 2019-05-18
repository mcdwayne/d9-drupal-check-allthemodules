(function ($, window, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.updateFlowchart = function () {
    showFlowchart(document.getElementById('graph_definition').value);
  };

})(jQuery, window, Drupal, drupalSettings);
