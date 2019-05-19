(function ($, Drupal) {

  "use strict";
  
  Drupal.behaviors.w3c = {
      attach: function (context, settings) {
      var $items = $('#w3c-report .w3c_validator-wrapper').once('w3cvalidatorcollapse').each(function () {
        var wrapper = $(this);

        // Turn the project title into a clickable link.
        // Add an event to toggle the content visibiltiy.
        var $legend = $('.page-summary > .title', wrapper);
        var $link = $('<a href="#"></a>')
          .prepend($legend.contents())
          .appendTo($legend)
          .click(function () {
            var result = $('.analysis-results', wrapper).toggleClass('open').toggle();
            return false;
          });
      });
      //$items.w3cvalidatorcollapse();
    }
  };

})(jQuery, Drupal);