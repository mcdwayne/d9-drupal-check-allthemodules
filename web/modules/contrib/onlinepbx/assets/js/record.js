/**
 * @file
 * Author: Synapse-studio.
 */

(function ($) {
  $(document).ready(function () {
    console.log('record');
    $('.call-record').click(function (event) {
      event.preventDefault();
      var $uuid = $(this).data('uuid');
      var $start = $(this).data('start');
      $(this).html("&nbsp; &nbsp;...").parent().css("padding", "4px 0 0");
      $.ajax({
        method: "POST",
        url: "/onlinepbx/record/" + $uuid + "/rec.mp3?",
        data: {_ajax: true}
      }).done(function (data) {
        if (data.success) {
          $("#call-" + $uuid).replaceWith(data.audio);
          $('audio#rec-' + $uuid).bind('canplay', function () {
            if (!$(this).hasClass("can-play")) {
              $(this).addClass("can-play");
              this.currentTime = $start;
            }
          });
        }
        else {
          console.log(data);
          $("#call-" + $uuid).html(data.error);
        }
      });
    });
  });
})(this.jQuery);
