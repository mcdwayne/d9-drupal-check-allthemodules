(function ($) {
  'use strict';
  Drupal.behaviors.awesome = {
    attach: function (context, settings) {
      function startTime() {
        var today = new Date();
        var hr = today.getHours();
        var min = today.getMinutes();
        var sec = today.getSeconds();
        min = checkTime(min);
        sec = checkTime(sec);
        jQuery('#analog-digital-clock-ampm #clock').html(hr + ' : ' + min + ' : ' + sec);
        var months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        var curWeekDay = days[today.getDay()];
        var curDay = today.getDate();
        var curMonth = months[today.getMonth()];
        var curYear = today.getFullYear();
        var date = curWeekDay + ', ' + curDay + ' ' + curMonth + ' ' + curYear;
        jQuery('#analog-digital-clock-ampm #date').html(date);
        setTimeout(function () { startTime(); }, 500);
      }
      function checkTime(i) {
        if (i < 10) {
          i = '0' + i;
        }
        return i;
      }
      startTime();
    }
  };
}(jQuery));
