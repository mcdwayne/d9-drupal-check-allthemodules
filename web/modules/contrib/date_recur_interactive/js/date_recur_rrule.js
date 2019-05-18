/**
 * @file
 * Date Recur Rrule Editor
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.dateRecurRruleWidget = {
    attach: function (context, settings) {
      $('textarea[data-date-recur-rrule]', context).each(function (idx, el) {
        var $rrule = $(el);
        if ($rrule.data('date-recur-init')) {
          return;
        }
        $rrule.data('date-recur-init', true);
        var id = $rrule.attr('data-date-recur-rrule');
        var $startDate = $('input[type=date][data-date-recur-start=' + id + ']', context);
        var rrule = $rrule.val();

        var $checkbox = $('<label><input type="checkbox">' + Drupal.t('Repeat?') + '</label>');
        $checkbox.insertAfter($rrule);
        $checkbox.find('input').prop('checked', rrule.length);
        var $widget = $('<div class="date-recur-widget"></div>').insertAfter($checkbox);

        function initWidget($widget) {
          var opts = {};
          if (rrule.length) {
            opts.rrule = rrule
          }
          opts.dtstart = new Date($startDate.val());
          $widget.recurringinput(opts);
          $widget.on('rrule-update', function () {
            rrule = $('.rrule-output', $widget).html();
            $rrule.val(rrule);
          });
        }

        $checkbox.find('input').on('change', function () {
          if ($(this).is(':checked')) {
            if (!$widget.data('date-recur-init')) {
              initWidget($widget);
              $widget.data('date-recur-init', true);
            }
            $widget.show();
          }
          else {
            $widget.hide();
            $rrule.val('');
          }
        });

        $startDate.on('change', function () {
          $checkbox.find('input').prop('disabled', !$startDate.val());
        });

        // Trigger events for init values.
        $checkbox.find('input').trigger('change');
        $startDate.trigger('change');

        // Hide original input field and label.
        $rrule.hide();
        $rrule.siblings('label[for="' + $rrule.attr('id') + '"]').hide();

        // Don't submit the widget inputs.
        $rrule.parents('form').first().submit(function() {
          $widget.find(':input').removeAttr('name');
        })
      });
    }
  };
}(jQuery, Drupal));
