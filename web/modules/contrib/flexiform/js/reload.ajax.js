(function($, Drupal) {
  Drupal.AjaxCommands.prototype.reload = function(ajax, response, status) {
    location.reload();
  }
})(jQuery, Drupal);
