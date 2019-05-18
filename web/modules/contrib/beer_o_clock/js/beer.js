jQuery(document).ready(function ($) {

  $('#liquid') // I Said Fill 'Er Up!
    .delay(3400)
    .animate({
      height: (220 * drupalSettings.beer_o_clock.percentage_full) + 'px'
    }, 2500);

  $('.beer-foam') // Keep that Foam Rollin' Toward the Top! Yahooo!
    .delay(3400)
    .animate({
      bottom: (250 * drupalSettings.beer_o_clock.percentage_full) + 'px'
      }, 2500);

});
