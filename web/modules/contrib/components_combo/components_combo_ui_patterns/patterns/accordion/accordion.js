(function ($, Drupal) {
Drupal.behaviors.pronatura_accordions = {
  attach: function (context, settings) {
    $(".js-accordion").accordion({
      collapsible: true
    });
  }
};
})(jQuery, Drupal);
