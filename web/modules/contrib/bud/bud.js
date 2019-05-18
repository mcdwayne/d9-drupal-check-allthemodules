(function ($) {

  Drupal.ajax = Drupal.ajax || {};
  Drupal.ajax.prototype = Drupal.ajax.prototype || {};
  Drupal.ajax.prototype.commands = Drupal.ajax.prototype.commands || {};

  Drupal.ajax.prototype.commands.bud_move = function (ajax, response, status) {
    block = $(response.block1).detach();
    $(block).insertBefore($(response.block2));
  }

})(jQuery);
