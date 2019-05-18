/**
 * @file
 * This file provides the javascript for the countdown event module
 *
 * Author: Mark Collins
 */
(function ($, Drupal, drupalSettings, document, window, undefined) {
  'use strict';
  Drupal.behaviors.countdown_event = {
    attach: function (context, settings) {
      // Convert event date to milliseconds.
      var countdownDate = (drupalSettings.countdown_event.countdownEvent.countdown_event_date * 1000);
      // event date/time from the countdown module with timezone offset correction.
      var labelMsg = (drupalSettings.countdown_event.countdownEvent.countdown_event_label_msg);
      var labelColor = (drupalSettings.countdown_event.countdownEvent.countdown_event_label_color);
      var textColor = (drupalSettings.countdown_event.countdownEvent.countdown_event_text_color);
      var backgroundColor = (drupalSettings.countdown_event.countdownEvent.countdown_event_background_color);

      // set defaults if no user preferences.
      if (labelMsg === '' || labelMsg === null) {
        labelMsg = 'COUNTDOWN:';
      }
      if (labelColor === '' || labelColor === null) {
        labelColor = '#000000';
      }
      if (backgroundColor === '' || backgroundColor === null) {
        backgroundColor = '#444444';
      }
      if (textColor === '' || textColor === null) {
        textColor = '#ffffff';
      }

      // add the custom message.
      document.getElementById('label_msg').innerHTML = labelMsg;

      // add the custom label color.
      var label = document.getElementById('countdown-event-clock-holder');
      label.style.color = labelColor;
      var units = ['countdownDays', 'countdownHrs', 'countdownMins', 'countdownSecs'];

      // add the custom background color and text color.
      function customStyle(units, bgColor, txtColor) {
        units.forEach(function (element) {
          var styledDiv = document.getElementById(element);
          styledDiv.style.backgroundColor = bgColor;
          styledDiv.style.color = txtColor;
          // add class style.

          // For IE.
          styledDiv.setAttribute('class', 'countdownDigitBk');
          // For Most Browsers
          styledDiv.setAttribute('className', 'countdownDigitBk');
        });
      }

      // apply styling by calling function.
      customStyle(units, backgroundColor, textColor);
      setInterval(function () {

        // get todays date / time.
        var nowDateTime = new Date();

        // ascertain the difference between today the countdown event date / time.
        var totalMilSecs = nowDateTime - countdownDate;

        // round the number down and make it positive.
        var totalSecs = Math.floor(((totalMilSecs / 1000)) * -1);

        // time units expressed in secs.
        var singleDay = 60 * 60 * 24;
        var singleHour = 60 * 60;
        var singleMinute = 60;
        var singleSec = 1;

        // calculate days.
        var dayDiffDays = Math.floor(totalSecs / singleDay);

        // calculate hours.
        var hourDiffHours = Math.floor((totalSecs / singleHour) - (dayDiffDays * 24));

        // calculate mins.
        var minDiffMins = Math.floor((totalSecs / singleMinute) - ((hourDiffHours * 60) + (dayDiffDays * 24 * 60)));

        // calculate secs.
        var secDiffSecs = Math.floor((totalSecs / singleSec) - ((minDiffMins * 60) + (hourDiffHours * 60 * 60) + (dayDiffDays * 60 * 60 * 24)));

        // function to add leading zero to tidy display.
        var addLeadZero = function (number) {

          return ((number < 10) ? ('0' + number) : number);
        };

        hourDiffHours = addLeadZero(hourDiffHours);
        minDiffMins = addLeadZero(minDiffMins);
        secDiffSecs = addLeadZero(secDiffSecs);

        var nodes = [];
        nodes[0] = document.getElementById('countdownDays');
        nodes[1] = document.getElementById('countdownHrs');
        nodes[2] = document.getElementById('countdownMins');
        nodes[3] = document.getElementById('countdownSecs');

        // remove existing countdown if present.
        if (nodes[0].firstChild) {
          var removeNode = function (nodeName) {
            nodeName.removeChild(nodeName.childNodes[0]);
          };

          for (var i = 0; i < nodes.length; i++) {
            removeNode(nodes[i]);
          }
        } // end if.

        // update countdown.
        var updateCountdown = function (node, timeUnit, elementId) {

          // add to dom tree.
          var textNode = document.createTextNode(timeUnit);
          node.appendChild(textNode);
        };

        updateCountdown(nodes[0], dayDiffDays, 'countdownDays');
        updateCountdown(nodes[1], hourDiffHours, 'countdownHrs');
        updateCountdown(nodes[2], minDiffMins, 'countdownMins');
        updateCountdown(nodes[3], secDiffSecs, 'countdownSecs');

        // repeat every second.
      }, 1000);
    }
  };
})(jQuery, Drupal, drupalSettings, this.document, this);

