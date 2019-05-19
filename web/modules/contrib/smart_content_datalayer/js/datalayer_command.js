(function (Drupal) {

  'use strict';

  Drupal.AjaxCommands.prototype.dataLayerCommand = function (ajax, response, status) {
    window.dataLayer.push(response.data);
  };

}(Drupal));
