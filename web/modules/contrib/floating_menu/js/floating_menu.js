/**
 * @file
 * Javascript functionality.
 */

(function ($, Drupal) {
  $('.popup-item').hover(
    function () {
      $(this).children('.item-text').stop().animate({right: '60px'});
    },
    function () {
      $(this).children('.item-text').stop().delay(500).animate({right: '-240px'});
    }
  );
})(jQuery, Drupal);
