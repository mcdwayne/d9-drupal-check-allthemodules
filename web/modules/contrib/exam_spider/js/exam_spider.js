/**
 * @file
 * Exam custom slider multiple answer checkbox enable/disable.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.exam_spider = {
    attach: function attach(context, settings) {
      if (drupalSettings.getTimeLimit) {
        countdownto();
      }
      function countdownto() {
        var TimeLimit = new Date(drupalSettings.getTimeLimit);
        var forForm = drupalSettings.forForm;
        var date = Math.round((TimeLimit - new Date()) / 1000);
        var hours = Math.floor(date / 3600);
        date = date - (hours * 3600);
        var mins = Math.floor(date / 60);
        date = date - (mins * 60);
        var secs = date;
        if (hours < 10) {
          hours = '0' + hours;
        }
        if (mins < 10) {
          mins = '0' + mins;
        }
        if (secs < 10) {
          secs = '0' + secs;
        }
        var elem = document.getElementById('exam_timer');
        if (typeof elem !== 'undefined' && elem !== null && parseInt(hours + mins + secs) !== 0) {
          document.getElementById('exam_timer').innerHTML = hours + ':' + mins + ':' + secs;
        }
        if (hours === '00' && mins === '00' && secs === '00') {
          $('a.exam_spider_slide_next.button').disabled = true;
          document.getElementById(forForm).submit();
        }
        setTimeout(countdownto, 1000);
      }

      var answercheckbox = $('.answer.form-checkbox');
      removedisable();
      $('#edit-multi-answer').click(function () {
        answercheckbox.prop('checked', false);
        answercheckbox.prop('disabled', false);
      });
      answercheckbox.click(function () {
        if (!$('#edit-multi-answer').is(':checked')) {
          if ($(this).is(':checked')) {
            answercheckbox.attr('disabled', 'disabled');
            $(this).prop('disabled', false);
          }
          else {
            answercheckbox.prop('disabled', false);
          }
        }
      });

      function removedisable() {
        if (!$('#edit-multi-answer').is(':checked')) {
          answercheckbox.each(function (i) {
            if ($('#edit-answer' + i).is(':checked')) {
              answercheckbox.attr('disabled', 'disabled');
              $('#edit-answer' + i).prop('disabled', false);
            }
          });
        }
      }

      function exam_spider_slider() {
        if ($('.exam_spider_slider_exam').find('.show')) {
          var current_id = $('.exam_spider_slider_exam').find('.show').attr('id');
          if (current_id) {
            var numeric_id = current_id.split('_');
            var counter_next = parseInt(numeric_id[1]) + parseInt(1);
            var total_slides = $('.exam_spider_slider_exam li').length;
            if (counter_next < total_slides) {
              $('.exam_spider_slider_exam .exam_spider_slider').removeClass('show');
              $('#examslide_' + counter_next).addClass('show');
            }
            else if (counter_next === total_slides) {
              $('.exam_spider_slider_exam .exam_spider_slider').removeClass('show');
              $('#examslide_' + counter_next).addClass('show');
              $('.exam_spider_slide_next').hide();
            }
          }
        }
      }

      $('.exam_spider_slider_exam .exam_spider_slider:first-child').addClass('show');
      $('.exam_spider_slide_next').once('a').on('click', function () {
        exam_spider_slider();
      });
    }
  };
}(jQuery, Drupal, drupalSettings));
