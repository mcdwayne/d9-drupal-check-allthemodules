(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the JS test behavior to to weight div.
   */
  Drupal.behaviors.jsFrequency = {
    attach: function (context, settings) {
    var frequency = drupalSettings.modal_blocks.frequency;
    var period = drupalSettings.modal_blocks.period;
    var random= drupalSettings.modal_blocks.random;
    var date = new Date();
    date.setTime(date.getTime() + period);
    $.cookie("random", " ", { expires: date });
    if (($.cookie(random) == undefined) || ($.cookie(random) == null)) {
      $.cookie(random, 0 );
    } 
    else {
      var cookie_value = $.cookie(random);
      cookie_value = parseInt(cookie_value);
      if (cookie_value < frequency ) {
        $(".modal").css("display", "block");
        var modal = $('#modal-block');
        var close = $('#modal-block-close')[0]; 
        var con = modal.parent();
        var parDiv = con.parent();
        $(".modal-block-close").on('click',function () {
        $(".modal").css("display", "none");
        $(".block-modalblock").css("dispaly","none");
        $(".parDiv").css("display", "none"); 
        });
        var cookie_value = cookie_value + 1;
        console.log($.cookie(random, cookie_value, { expires: date }));
      }
      else {
        $(".modal").css("display", "none");  
      }
    }
    }
  };
})(jQuery, Drupal, drupalSettings);