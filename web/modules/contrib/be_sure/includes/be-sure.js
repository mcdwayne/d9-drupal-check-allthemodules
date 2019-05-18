/**
 * @file
 * JavaScript for Be sure module.
 */

(function ($) {
  "use strict";
  Drupal.behaviors.beSure = {
    attach: function (context, settings) {
      $('#be-sure-tabs a').once('be-sure').click(function (e) {
        e.preventDefault();

        var $this = $(this);
        $('#be-sure-tabs li').removeClass('active');
        $this.closest('li').addClass('active');

        var id = $this.attr('href');
        $('#be-sure .element').hide();
        // We should remove first symbol is related to https://www.drupal.org/node/1543750
        $(id.substring(1)).show();
      });
    }
  };

  Drupal.behaviors.beSureStatusBar = {
    attach: function (cotnext, settings) {
      // Get percentage.
      $('div.progress div.bar div.filled').each(function () {
        var percentage = $(this).closest('div.progress').find('div.percentage').text();
        $(this).animate({width: percentage}, 500);
      });
    }
  };

})(jQuery);
