/**
 * @file
 * Provides Slick Browser widget utility functions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Browser widget utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} widget
   *   The Slick Browser widget HTML element.
   */
  function sbWidget(i, widget) {
    var $widget = $(widget);
    var widgetId = $widget.attr('id');
    var $slider = $('.slick__slider', widget);
    var $sliderMain = $('.slick--browser .slick__slider', widget);
    var $sbAction = $('.sb__header', widget);
    var end = $widget.data('end') ? $widget.data('end') : 0;
    var initialized = $sliderMain.hasClass('slick-initialized');

    /**
     * Clean up cloned ids.
     *
     * @name cleanUp
     */
    function cleanUp() {
      if (initialized) {
        $slider.find('.slick-cloned').removeAttr('data-entity-id data-row-id data-drupal-selector');

        $slider.on('setPosition', function () {
          $slider.find('.slick-cloned').removeAttr('data-entity-id data-row-id data-drupal-selector');
        });
      }
    }

    /**
     * Update the view of the working widget.
     *
     * @name updateView
     *
     * @param {jQuery.Event} event
     *   The event triggered, most likely a `click` event.
     */
    function updateView(event) {
      event.preventDefault();

      var $btn = $(event.currentTarget);
      var handled = $btn.data('handled');
      var target = $btn.data('target');
      var $targetId = $widget.siblings('input[type*=hidden][name*="[target_id]"]');

      if (target !== 'done') {
        $widget.data('deltas', '');
      }

      $widget.toggleClass('sb--' + target);
      $.each(['caption', 'crop', 'sort', 'done'], function (i, btn) {
        if (target === 'done' || target !== btn) {
          $widget.removeClass('sb--' + btn);
        }
      });

      $('.button--sb').removeClass('is-active');
      $('.button--' + target, widget)[$widget.hasClass('sb--' + target) ? 'addClass' : 'removeClass']('is-active');

      switch (target) {
        case 'crop':
          if (!handled && $sliderMain.length) {
            $sliderMain[0].slick.refresh();
          }
          break;

        case 'remove':
          var entityId = $btn.data('entityId') || $btn.closest('[data-entity-id]').data('entityId');
          var ids = $targetId.val();
          var existing = $widget.data('removedId') || '';

          $targetId.val($.trim(ids.replace(entityId, '')));
          $widget.data('removedId', existing + ' ' + entityId);

          rebuildSlick($btn);

          $widget.find('.sb__sortable [data-entity-id="' + entityId + '"]').remove();
          break;

        case 'removeall':
          $targetId.val('');
          $(widget).contents(':not(div[id*="ajax-wrapper"])').remove();
          rebuildSlick($btn);
          break;

        case 'done':
          if ($widget.data('deltas')) {
            rebuildSlick();
          }
          break;

        default:
          break;
      }

      $btn.data('handled', !handled);
      Drupal.slickBrowser.jump(widgetId);
      cleanUp();
    }

    /**
     * Rebuild the slick instances.
     *
     * @name rebuildSlick
     *
     * @param {jQuery.Object} $btn
     *   The triggering button HTML element.
     */
    function rebuildSlick($btn) {
      if (!$('.slick', widget).length) {
        return;
      }

      $('.slick', widget).each(function () {
        var $slider = $('.slick__slider', this);
        var $slide = $('.slide', $slider);
        var slick = $slider.slick('getSlick');
        var i = 0;
        var rebuild = false;

        if ($btn) {
          if ($btn.data('target') === 'removeall') {
            $slider.slick('removeSlide', null, null, true);
            // $(widget).contents(':not(div[id*="ajax-wrapper"])').remove();
            rebuild = true;
          }
          else {
            var index = $btn.closest('.slide').data('slickIndex');

            // No need for $slider.slick('refresh');.
            $slider.slick('slickRemove', index);

            $slide.each(function () {
              $(this).attr('data-slick-index', i);
              i++;
            });

            if ($slide.length === 1) {
              $(widget).empty();
              rebuild = true;
            }
          }
        }
        else {
          $slide.sort(function (a, b) {
            return $(a).data('rowId') - $(b).data('rowId');
          });

          $slider.empty();
          $slide.clone().detach().appendTo($slider);

          $slider.slick(slick.options);
          rebuild = true;
        }

        if (rebuild) {
          Drupal.attachBehaviors($widget[0]);
          if ($('.media--player', $slide).length) {
            Drupal.attachBehaviors($('.media--player', $slide)[0]);
          }
        }
      });
    }

    /**
     * Fixes for Focal Point indicator conflict with draggable.
     *
     * @name focalPoint
     */
    function focalPoint() {
      $('*[draggable!=true]', $sliderMain).unbind('dragstart');

      $sliderMain.on('draggable mouseenter mousedown', '.focal-point-indicator', function (e) {
        e.stopPropagation();
      });
    }

    /**
     * Reveal alt and title for just in case required, but left empty.
     *
     * @name revealText
     *
     * @param {jQuery.Event} event
     *   The event triggered, most likely a `click` event.
     */
    function revealText(event) {
      var $form = $(event.delegateTarget);
      var sbId = $('.sb.sb--error', $form).attr('id');
      var $slider = $('.sb .slick__slider', $form);
      var $text = $('.form-text.required', $slider);

      if (!$text.length) {
        return;
      }

      // Prevents false negative form validation with hidden cloned.
      $('.slick-cloned .form-text', $slider).removeAttr('required aria-required');

      $text.each(function () {
        var $that = $(this);
        $that.removeClass('error').removeAttr('tabindex');

        if (!this.value) {
          var empty = parseInt($that.closest('.slide').data('slickIndex'));

          $that.addClass('error');
          $that.closest('.sb--widget').addClass('sb--error sb--caption');
          $slider.slick('slickGoTo', empty, true);
          $slider.slick('slickPause');
        }
      });

      Drupal.slickBrowser.jump(sbId);
    }

    cleanUp();

    if ($widget.hasClass('sb--1')) {
      $('.slick__slide', widget).addClass('slick-current slick-active');
    }

    if (initialized && !$sbAction.find('.slick__arrow').length) {
      $sliderMain.siblings('.slick__arrow').appendTo($sbAction);
    }

    if (end > 1 && $('.focal-point-indicator', $sliderMain).length) {
      focalPoint();
    }

    var removedIds = $widget.data('removedId');
    if (removedIds) {
      var ids = removedIds.split(' ');
      $.each(ids, function (i, v) {

      });
      // console.log(removedIds);
    }

    $widget.data('deltas', '');

    $widget.off('click.btnSb').on('click.btnSb', '.button--sb', updateView);
    $('form').on('click.btnDo', '.publish .button', revealText);
  }

  /**
   * Slick Browser sortable utility functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} elm
   *   The sortable container HTML element.
   */
  function sbSortable(i, elm) {
    var $first = $('.sb__sortitem:first', elm);
    var h = $first.height();
    var w = $first.width();

    /**
     * Sort the elements.
     *
     * @name sortItems
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `sortable` event.
     */
    function sortItems(event) {
      var $target = $(event.target);
      var $items = $('.sb__sortitem', $target);
      var $widget = $target.closest('.sb--widget');
      var eb = $target.closest('.sb--eb').length;
      var ids = [];
      var deltas = [];
      var delta = 0;
      var item;
      var len = $items.length;

      $('.slick', $widget).each(function () {
        var $slider = $('.slick__slider', this);
        if ($slider.hasClass('slick-initialized')) {
          $slider.slick('unslick');
        }
      });

      // Cleans up unclean unslick.
      $widget.find('.slick__slide.slick-cloned').remove();

      // Update the slick slides to match the new ordered elements.
      for (var i = 0; i < len; i++) {
        item = $items[i];
        delta = $(item).data('rowId');
        deltas[i] = delta;

        $('.sb__weight', item).val(i);
        $('.sb__weight option', item).removeAttr('selected');
        $('.sb__weight option[value="' + i + '"]', item).prop('selected', true).siblings('option').prop('selected', false);

        $widget.find('.slick__slide.slide--' + delta).attr('data-slick-index', i).attr('data-row-id', i);
        $(item).attr('data-row-id', i);

        if (eb) {
          ids[i] = $(item).attr('data-entity-id');
        }
      }

      $widget.data('deltas', deltas);

      // Update entity browser target_id fields.
      if (eb) {
        $widget.siblings('input[type*=hidden][name*="[target_id]"]').val(ids.join(' '));
      }
    }

    // Build sortable items.
    $(elm).sortable({
      placeholder: 'sb__placeholder',
      items: '.sb__sortitem',
      stop: sortItems,
      cancel: 'a, input, .button',
      over: function (e, ui) {
        ui.placeholder.addClass('grid').css({
          height: 0,
          width: 0
        });
      },
      change: function (e, ui) {
        ui.placeholder.css({
          height: h,
          width: w
        });
      }
    });
  }

  /**
   * Attaches slick browser widget behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowserWidget = {
    attach: function (context) {
      $('.sb--widget', context).once('sbWidget').each(sbWidget);
      $('.sb__sortable', context).once('sbSortable').each(sbSortable);
      $('.js-form-managed-file .js-form-submit:not(.button--sb)', context).on('mousedown.sbAjax', Drupal.slickBrowser.loading);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $('.js-form-managed-file .js-form-submit:not(.button--sb)', context).off('.sbAjax');
        Drupal.slickBrowser.loaded();
      }
    }
  };

})(jQuery, Drupal);
