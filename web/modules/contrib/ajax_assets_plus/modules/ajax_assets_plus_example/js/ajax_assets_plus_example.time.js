(function($) {

  var date = new Date();
  var timeText = 'Current time: ' + date.getHours() + ':' + (date.getMinutes() < 10 ? '0' : '') + date.getMinutes();
  console.log(timeText);
  $('.ajax-assets-plus-example-date').append('<div class="ajax-assets-plus-example-date__time">' + timeText + '</div>');

})(jQuery);
