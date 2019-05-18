(function ($) {
  'use strict';
  Drupal.behaviors.animated_clock = {
    attach: function (context, settings) {
    var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    var newDate = new Date();
    newDate.setDate(newDate.getDate());
    $('#animated-clock-date').html(dayNames[newDate.getDay()] + ' ' + newDate.getDate() + ' ' + monthNames[newDate.getMonth()] + ' ' + newDate.getFullYear());
    setInterval(function () {
    var seconds = new Date().getSeconds();
    $('#animated-clock-sec').html((seconds < 10 ? '0' : '') + seconds);
  }, 1000);
    setInterval(function () {
    var minutes = new Date().getMinutes();
    $('#animated-clock-min').html((minutes < 10 ? '0' : '') + minutes);
  }, 1000);
    setInterval(function () {
    var hours = new Date().getHours();
    $('#animated-clock-hours').html((hours < 10 ? '0' : '') + hours);
  }, 1000);
  }
  };
}(jQuery));
