(function ($, Drupal) {
  Drupal.behaviors.MerciLineItemBehavior = {
    attach: function (context, settings) {
     $(context).find('input.myCustomBehavior').once('myCustomBehavior').each(function () {
      // Apply the myCustomBehaviour effect to the elements only once.
    });
    }
  };
})(jQuery, Drupal);

