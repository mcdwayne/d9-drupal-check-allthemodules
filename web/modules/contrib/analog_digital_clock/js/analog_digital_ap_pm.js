(function ($) {
  'use strict';
  Drupal.behaviors.awesome = {
    attach: function (context, settings) {
      function startTime() {
        var today = new Date();
        var hr = today.getHours();
        var min = today.getMinutes();
        var sec = today.getSeconds();
        var ap = (hr < 12) ? '<span>' + Drupal.t('AM') + '</span>' : '<span>' + Drupal.t('PM') + '</span>';
        hr = (hr === 0) ? 12 : hr;
        hr = (hr > 12) ? hr - 12 : hr;
        hr = checkTime(hr);
        min = checkTime(min);
        sec = checkTime(sec);
        jQuery('#analog-digital-clock-ampm #clock').html(hr + ':' + min + ':' + sec + ' ' + ap);
        var months = [
          Drupal.t('January'),
          Drupal.t('February'),
          Drupal.t('March'),
          Drupal.t('April'),
          Drupal.t('May'),
          Drupal.t('June'),
          Drupal.t('July'),
          Drupal.t('August'),
          Drupal.t('September'),
          Drupal.t('October'),
          Drupal.t('November'),
          Drupal.t('December')
        ];
        var days = [
          Drupal.t('Sun'),
          Drupal.t('Mon'),
          Drupal.t('Tue'),
          Drupal.t('Wed'),
          Drupal.t('Thu'),
          Drupal.t('Fri'),
          Drupal.t('Sat')
        ];
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
