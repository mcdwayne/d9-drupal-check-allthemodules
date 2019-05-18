/**
 * @file
 * Jquery for the curtain raiser inauguration effect.
 */

 jQuery(function ($) {'use strict',

  $('#curtain-raiser-inauguration-form').on('submit', function (e) {
    e.preventDefault();
    var pwd = $('#edit-inaugurate-password').val();
    var path = "ajax/inauguration/validate/" + pwd;
    $.ajax({
        type : 'GET',
        url : '/' + path,
        encode : true
    })
    .done(function (data) {
      if (data.success) {
        $("#curtain1").animate({width:20},6000);
        $("#curtain2").animate({width:20},6000);
        $(".curtain-raiser").addClass('pointer-none');
        $(".curtain-raiser-content").fadeOut();

      }
      else {
        $('.form-item-inaugurate-password').prepend("<div class = 'error'>Please enter the right password</div>");
      }
    });
  });

});
