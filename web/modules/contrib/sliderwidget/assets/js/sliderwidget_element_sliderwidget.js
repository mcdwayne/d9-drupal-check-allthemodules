(function ($, Drupal) {
  'use strict';

  /**
   * This script adds jQuery slider functionality to transform_slider form element.
   */
  Drupal.behaviors.SliderWidgetSliderWidget = {
    attach: function (context, settings) {

      // Create sliders.
      $('.sliderwidget-container:not(.sliderwidget-processed)', context).each(function () {

        $(this).addClass('sliderwidget-processed');
        var slider_id = $(this).parent().attr('id');
        var setting = settings['sliderwidget_' + slider_id];
        // Get values.
        var $slider = $(this).parents('.sliderwidget', context);

        var $values = [];
        var $value = $slider.find('.sliderwidget-value-field', context).val();
        var $value2 = $slider.find('.sliderwidget-value2-field', context).val();
        if (!isNaN($value2)) {
          $values = [$value, $value2];
        }
        else {
          setting.value = $value;
          setting.current_value = $value;
        }

        if (!setting.display_inputs) {
          $slider.find('.sliderwidget-value-field, .sliderwidget-value2-field', context).hide();
          $slider.find('label', $slider.find('.sliderwidget-value-field, .sliderwidget-value2-field', context).parents()).hide();
          $slider.find('label', $slider.find('.webform-sliderwidget .sliderwidget-value-field,.webform-sliderwidget .sliderwidget-value2-field', context).parents()).show();
        }

        // Setup slider.
        $(this).slider({
          value: $value,
          animate: setting.animate,
          max: setting.max - 0,
          min: setting.min - 0,
          orientation: setting.orientation,
          range: setting.range,
          step: setting.step,
          values: $values,
          slide: sliderwidgetsSlideProcess,
          stop: sliderwidgetsSlideStop,
          change: sliderwidgetsSlideChange,
          create: sliderwidgetsSlideCreate
        });

        $(document).bind('state:disabled', function (e) {
          // Only act when this change was triggered by a dependency and not by the
          // element monitoring itself.
          if (e.trigger) {
            var state = 'disable';
            if (e.value) {
              state = 'disable';
            }
            else {
              state = 'enable';
            }
            $(e.target).find('.sliderwidget-container').slider(state);
            $(e.target).find('select, input, textarea').attr('disabled', e.value);
          }
        });

        if (setting.disabled) {
          $(this).slider('disable');
        }

        sliderwidgetsSlideUpdateFields($slider, {value: $value, values: $values});

        // Adjust the range when the target field is changed
        if (setting.adjust_field_max_css_selector || setting.adjust_field_min_css_selector) {
          var adjust_field_css_selector = '';
          if (setting.adjust_field_max_css_selector) {
            adjust_field_css_selector = setting.adjust_field_max_css_selector;
          }
          if (setting.adjust_field_min_css_selector) {
            if (!adjust_field_css_selector) {
              adjust_field_css_selector += ',';
            }
            adjust_field_css_selector += setting.adjust_field_min_css_selector;
          }
          var adjust_field = $(adjust_field_css_selector);
          if (adjust_field.length) {
            adjust_field.bind('keyup', function (event) {
              var $target = $(event.target);
              var option_name = '';
              var target_value = parseInt($target.val());
              if (target_value) {
                if ($(setting.adjust_field_min_css_selector).index(event.target) !== -1) {
                  option_name = 'min';
                  $slider.find('.sliderwidget-min-value-field', context).val(target_value);
                }
                else if ($(setting.adjust_field_max_css_selector).index(event.target) !== -1) {
                  option_name = 'max';
                  $slider.find('.sliderwidget-max-value-field', context).val(target_value);
                }
                var $SliderWidget = $slider.find('.sliderwidget-container', context);
                $SliderWidget.slider('option', option_name, target_value);
                $SliderWidget.slider('option', {
                  value: $SliderWidget.slider('value'),
                  values: $SliderWidget.slider('values')
                });
              }
            });
            adjust_field.trigger('keyup');
          }
        }

        if (setting.hide_slider_handle_when_no_value && $value === '') {
          $slider.find('.ui-slider-handle').hide();
        }
      });
    }
  };

  // Slider stop.
  var sliderwidgetsSlideStop = function ($slider, ui) {
    $slider = $(this).parents('.sliderwidget');

    // Execute a change on the value text areas to initiate any ajax calls.
    $slider.find('.sliderwidget-value-field').change();
    $slider.find('.sliderwidget-value2-field').change();
  };

  // Slider update related fields.
  var sliderwidgetsSlideUpdateFields = function ($slider, ui) {
    var $slider_id = $slider.attr('id');
    var setting = drupalSettings['sliderwidget_' + $slider_id];

    var $values = [];
    if ($slider.find('.sliderwidget-value2-field').length > 0 && setting.multi_value) {
      $slider.find('.sliderwidget-value-field').val(ui.values[0]);
      $slider.find('.sliderwidget-value2-field').val(ui.values[1]);
      $values = ui.values;
    }
    else {
      $slider.find('.sliderwidget-value-field').val(ui.value);
      $values.push(ui.value);
    }

    var i;
    if (setting.fields_to_sync_css_selector) {
      if (Array.isArray(setting.fields_to_sync_css_selector) && setting.multi_value) {
        for (i = 0; i < setting.fields_to_sync_css_selector.length; i++) {
          $(setting.fields_to_sync_css_selector[i]).val(ui.values[i]);
        }
      }
      $(setting.fields_to_sync_css_selector).val(ui.value);
    }

    for (i = 0; i < $values.length; i++) {
      // Update handler bubble.
      if (setting.display_bubble) {
        var bubble_value = '';
        if (setting.display_bubble_format.indexOf('||') > 0) {
          var bubble_formats = setting.display_bubble_format.split('||');
          bubble_value = bubble_formats[i].replace('%{value}%', $values[i]);
        }
        else {
          bubble_value = setting.display_bubble_format.replace('%{value}%', $values[i]);
        }
        $('#' + $slider_id + ' .ui-slider-handle:eq(' + i + ') .sliderwidget-bubble').html(bubble_value);
      }
      $values[i] = setting.display_values_format.replace('%{value}%', $values[i]);
    }
    $slider.find('.sliderwidget-display-values-field').html($values.join(' - '));
  };

  // Slider change.
  var sliderwidgetsSlideChange = function (event, ui) {
    var $slider = $(this).parents('.sliderwidget');
    sliderwidgetsSlideUpdateFields($slider, ui);
    var $slider_id = $slider.attr('id');
    var setting = drupalSettings['sliderwidget_' + $slider_id];
    $slider.find('.ui-slider-handle').show();

    // Sync other sliders in the same group.
    if (setting.group) {
      var $group_sliders = $('.sliderwidget:[id!="' + $slider_id + '"].sliderwidget-group-' + setting.group);
      if ($('.sliderwidget:[id!="' + $slider_id + '"].sliderwidget-group-master.sliderwidget-group-' + setting.group).length < 1) {
        var $group_slider;
        var $group_slider_settings;
        var $group_ui;
        for (var i = 0; i < $group_sliders.length; i++) {
          $group_slider = $($group_sliders[i]);
          $group_ui = $group_slider.find('.sliderwidget-container');
          $group_slider_settings = drupalSettings['sliderwidget_' + $group_slider.attr('id')];
          sliderwidgetsSlideUpdateFields($group_slider, {value: $group_ui.slider('value'), values: $group_ui.slider('values')});
          $group_slider_settings.value = $group_ui.slider('value');
        }
      }
    }

  };

  var sliderwidgetsSlideCreate = function (event, ui) {
    var $slider = $(this).parents('.sliderwidget');
    var $slider_id = $slider.attr('id');
    var setting = drupalSettings['sliderwidget_' + $slider_id];

    // Add bubble to each handler.
    if (setting.display_bubble) {
      var handle = $(this).find('.ui-slider-handle');
      var bubble_value = '';
      var bubble = $('<div class="sliderwidget-bubble-wrapper"><div class="sliderwidget-bubble">' + bubble_value + '</div></div>');
      handle.append(bubble);
    }
  };

  // Slider processor.
  var sliderwidgetsSlideProcess = function (event, ui) {
    var $slider = $(this).parents('.sliderwidget');
    sliderwidgetsSlideUpdateFields($slider, ui);
    var $slider_id = $slider.attr('id');
    var setting = drupalSettings['sliderwidget_' + $slider_id];

    // Sync other sliders in the same group.
    if (setting.group) {
      var $value_diff_orig = ui.value - setting.value;
      var $group_sliders = $('.sliderwidget:[id!="' + $slider_id + '"].sliderwidget-group-' + setting.group);
      if ($('.sliderwidget:[id!="' + $slider_id + '"].sliderwidget-group-master.sliderwidget-group-' + setting.group).length < 1) {
        var $group_slider;
        var $group_slider_settings;
        var $group_ui;
        var $items = [];
        var $total_diff = $value_diff_orig;
        var $total_diff_items_no = $group_sliders.length;
        var $val;

        for (var i = 0; i < $group_sliders.length; i++) {
          $group_slider = $($group_sliders[i]);
          $group_slider_settings = drupalSettings['sliderwidget_' + $group_slider.attr('id')];
          $items[i] = {value: $group_slider_settings.value, index: i};
        }
        var sortFunc = function (data_A, data_B) {
          return (data_A.value - data_B.value);
        };
        $items.sort(sortFunc);

        for (var j = 0; j < $group_sliders.length; j++) {
          var n = $items[j].index;
          $group_slider = $($group_sliders[n]);
          $group_ui = $group_slider.find('.sliderwidget-container');
          $group_slider_settings = drupalSettings['sliderwidget_' + $group_slider.attr('id')];
          $group_ui.slider({slide: function () {}, change: function () {}});

          if (setting.group_type === 'same') {
            $group_ui.slider('value', ui.value);
          }

          if (setting.group_type === 'lock') {
            $group_ui.slider('value', $group_slider_settings.value + $value_diff_orig);
          }

          if (setting.group_type === 'total') {
            $val = $group_slider_settings.value - ($total_diff / $total_diff_items_no);
            $total_diff = $total_diff - ($total_diff / $total_diff_items_no);
            $total_diff_items_no = $total_diff_items_no - 1;
            if ($val < 0) {
              $total_diff = $total_diff + (-1 * $val);
              $val = 0;
            }
            $group_ui.slider('value', $val);
          }

          $group_ui.slider({slide: sliderwidgetsSlideProcess, change: sliderwidgetsSlideChange});
          sliderwidgetsSlideUpdateFields($group_slider, {value: $group_ui.slider('value'), values: $group_ui.slider('values')});
        }
      }
    }
  };

})(jQuery, Drupal);
