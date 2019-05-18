(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Custom ajax command for mobile ID status check.
   *
   * @param {Drupal.Ajax} ajax
   *  {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   The response from the Ajax request..
   */
  Drupal.AjaxCommands.prototype.auth_status_check_command = function (ajax, response) {
    $.ajax({
      url: response.path
    }).done(function (data) {
      var commands = new Drupal.AjaxCommands();
      var element_settings = {
        url: this.url
      };
      var ajax = new Drupal.Ajax('eid-auth-progress', null, element_settings);
      for (var i in data) {
        if (data.hasOwnProperty(i) && data[i].command && commands[data[i].command]) {
          commands[data[i].command](ajax, data[i], 200);
        }
      }
    });
  };

})(jQuery, Drupal, drupalSettings);
