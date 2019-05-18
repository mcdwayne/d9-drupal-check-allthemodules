(function ($, Drupal) {
  Drupal.AjaxCommands.prototype.cleanReplace = function (ajax, response, status) {
    $(response.selector).html(response.element);
  }
})(jQuery, Drupal);