(function ($, window, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.updateOptionsCommand = function (ajax, response, status) {
    var elementId = response.elementId;
    var options = response.options;
    var select = document.getElementById(elementId);

    select.options.length = 0;
    for (var i = 0; i <= options.length; i++) {
      if (options.hasOwnProperty(i)) {
        select.options.add(new Option(options[i].value, options[i].key));
      }
    }

    var event = new Event('change');
    select.dispatchEvent(event);
  };

})(jQuery, window, Drupal, drupalSettings);
