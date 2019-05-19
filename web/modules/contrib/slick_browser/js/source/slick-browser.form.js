/**
 * @file
 * Provides Slick Browser utilitiy functions.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Slick Browser utility functions.
   *
   * @param {int} j
   *   The index of the current element.
   * @param {HTMLElement} form
   *   The Entity Browser form HTML element.
   */
  function sbForm(j, form) {
    var $form = $(form);
    var $wParent = $(window.parent.document);
    var $dialog = $('.ui-dialog:visible', $wParent);
    var $footer = $('#edit-footer', form);
    var $checkBox = $('input[name*="entity_browser_select"]', form);
    var $btnUse = $('.button[name="use_selected"]', form);
    var txtUse = $btnUse.length ? $btnUse.val() : Drupal.t('Add to Page');
    var clonedUse = '#edit-use-selected-clone';
    var $selectedWrap = $('#edit-selected', form).removeClass('hidden');
    var cardinality = $form.data('cardinality') || -1;

    /**
     * Selects item within Entity Browser iframes.
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `click` event.
     *
     * @return {bool}|{mixed}
     *   Return false if no context available.
     */
    function addItem(event) {
      event.preventDefault();

      var grid = event.currentTarget;

      checkItem(grid);

      // Only multistep display has selection, so do nothing.
      if (!$selectedWrap.length) {
        $form.addClass('is-collapsed');
        return false;
      }

      // Show the selection button.
      $('.button-wrap--show-selection', form).show();

      // Refresh selection sortable.
      $selectedWrap.sortable('refresh');
      $form.removeClass('is-collapsed')
    }

    /**
     * Clones item into selection display.
     *
     * @param {HTMLElement} grid
     *   The grid HTML element.
     */
    function cloneItem(grid) {
      var $grid = $(grid);

      var $input = $('input[name^="entity_browser_select"]', grid);
      var entity = $input.val();
      var split = entity.split(':');
      var id = split[1];
      var $img = $('img', grid);
      var thumb = $('.media', grid).data('thumb');
      // @todo proper preview selection.
      var $txt = $('.views-field--selection', grid).length ? $('.views-field--selection', grid) : $('.views-field:nth-child(2)', grid);
      var $clone = null;

      $form.removeClass('is-empty');

      $grid.attr('data-entity-id', id).attr('data-entity', entity);

      // If it has thumbnails.
      if (thumb) {
        $clone = $('<img src="' + thumb + '" alt="' + Drupal.t('Thumbnail') + '">');
      }
      // If it has images.
      else if ($img.length) {
        $clone = $img;
      }
      // If it has no images, and has a special class .views-field--selection.
      // @todo fault proof.
      else if ($txt.length) {
        $clone = $txt;
      }

      if ($clone === null) {
        return;
      }

      // Only multistep display has selection, so do nothing.
      if (!$selectedWrap.length) {
        return;
      }

      $clone.clone()
        .addClass('item-selected')
        .detach()
        .appendTo($selectedWrap)
        .wrapAll('<div class="item-container" data-entity-id="' + id + '" data-entity="' + entity + '" />').once();

      // Adds dummy elements for quick interaction.
      var $weight = '<input class="weight" value="" type="hidden" />';
      var $remove = '<span class="button-wrap button-wrap--remove"><input value="Remove" class="button button--remove button--remove-js" type="button"></span>';
      $('.item-container', $selectedWrap).each(function (i) {
        var t = $(this);

        if (!$('.weight', t).length) {
          t.append($remove);
          t.append($weight);

          $('.button--remove', t).attr('data-entity', entity).attr('data-remove-entity', 'items_' + entity).attr('name', 'remove_' + id + '_' + i);
          $('.weight', t).val(i).attr('name', 'selected[items_' + id + '_' + i + '][weight]');
        }
      });

      // Remove the clone when the input is unchecked.
      if (!$input.prop('checked')) {
        $selectedWrap.find('.item-container[data-entity="' + entity + '"]').remove();
      }
    }

    /**
     * Check the EB input when the outer element is clicked.
     *
     * @param {HTMLElement} grid
     *   The grid HTML element.
     *
     * @return {bool}
     *   Return false if not applicable.
     */
    function checkItem(grid) {
      var $grid = $(grid);
      var input = 'input[name^="entity_browser_select"]';
      var $input = $(input, grid);
      var entity = $input.val();
      var $view = $grid.closest('.view--sb');

      var checkOne = function () {
        $input.prop('checked', !$input.prop('checked')).attr('data-entity', entity);
        $grid[$input.prop('checked') ? 'addClass' : 'removeClass']('is-marked is-checked');

        $('.button--select', grid).html($input.prop('checked') ? '&#10003;' : '&#43;');
      };

      var uncheckOne = function () {
        $input.prop('checked', false);
        $grid.removeClass('is-marked is-checked');
        $('.button--select', grid).html('&#43;');

        if ($selectedWrap.length) {
          $selectedWrap.find('.item-container[data-entity="' + entity + '"]').remove();
        }
      };

      var resetAll = function () {
        $(input).not($grid.find('input')).prop('checked', false);
        $view.find('.grid').not(this).removeClass('is-marked is-checked');
      };

      switch (cardinality) {
        case 1:
          // Do not proceed if one is already stored, until removed.
          if ($view.find('.was-checked').length) {
            return false;
          }

          resetAll();
          checkOne();
          cloneItem(grid);

          // Remove anything else but the new one selected.
          if ($selectedWrap.length) {
            $selectedWrap.find('.item-container:not([data-entity="' + entity + '"])').remove();
          }
          break;

        case -1:
          checkOne();
          cloneItem(grid);
          break;

        default:
          var total = $view.find('.is-marked').length + 1;
          $form[total === cardinality ? 'addClass' : 'removeClass']('form--overlimit');
          if (total > cardinality) {
            // @todo resetOne, checkOne? Or let the user remove one instead?
            if ($grid.hasClass('is-checked')) {
              uncheckOne();
              $form.removeClass('form--overlimit');
            }
            else {
              $form.addClass('form--overlimit');
            }
            return false;
          }
          else {
            checkOne();
            cloneItem(grid);
          }
          break;
      }
    }

    /**
     * Removes item within Entity Browser selection display.
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `click` event.
     */
    function removeItem(event) {
      event.preventDefault();

      var $btn = $(event.currentTarget);
      var $item = $btn.closest('.item-container');
      var entity = $item.data('entity');
      var grid = $btn.closest('.grid');

      var $input = $(event.delegateTarget).find('input[name="entity_browser_select[' + entity + ']"]');
      var $marked = $form.find('.is-marked[data-entity="' + entity + '"]');

      // Remove markers from input container.
      $marked.removeClass('is-marked is-checked');

      $input.prop('checked', false).closest('.is-checked').removeClass('is-marked is-checked');

      // Remove selection item as well.
      $item.remove();
      $form.removeClass('form--overlimit');
      $('.button--select', $marked).html('&#43;');

      if (isEmpty()) {
        doEmpty();
      }
    }

    /**
     * Toggles the selection displays.
     *
     * @param {jQuery.Event} event
     *   The event triggered by a `click` event.
     */
    function toggleSelections(event) {
      var $btn = $(event.currentTarget);

      // $btn.parent().toggleClass('is-active');
      $form.toggleClass('is-collapsed');

      if ($form.hasClass('form--selection-v') && $form.find('.slick__slider').length) {
        // Manually refresh positioning of slick as the layout changes.
        window.setTimeout(function () {
          $form.find('.slick__slider')[0].slick.refresh();
        }, 40);
      }
    }

    /**
     * Checks if selection is empty.
     *
     * @return {bool}
     *   True if a selection is not available, else false.
     */
    function isEmpty() {
      return !$footer.find('.item-container').length;
    }

    /**
     * Do something if selection is empty.
     */
    function doEmpty() {
      $('.button-wrap--show-selection', $footer).hide();
      $form.addClass('is-empty is-collapsed');
    }

    /**
     * Marks selected item disabled to avoid dup selection in the first place.
     *
     * @param {int} i
     *   The index.
     * @param {HTMLElement} elm
     *   The item HTML element.
     */
    function disableSelected(i, elm) {
      var entityId = $(elm).data('entityId');
      var type = $checkBox.val().split(':')[0];
      var $inputSelected = $('input[name="entity_browser_select[' + type + ':' + entityId + ']"]', form);
      var txtSelected = Drupal.t('Was selected');
      var $grid;

      if ($inputSelected.length) {
        $inputSelected.prop('checked', false).attr('disabled', 'disabled');
        $grid = $inputSelected.closest('.grid').addClass('is-marked was-checked');
        if ($grid.find('img').length) {
          $grid.find('img').attr('title', txtSelected);
        }
        else {
          $grid.attr('title', txtSelected);
        }
      }
    }

    /**
     * Dialog actions.
     */
    function doDialog() {
      var $fake = $('<button id="edit-use-selected-clone" class="button button--primary button--sb button--use-selected-clone">' + txtUse + '</button>');
      var $close = $dialog.eq(0).find('.ui-dialog-titlebar-close');

      if ($btnUse.length && !$dialog.find(clonedUse).length) {
        $fake.insertBefore($close);
      }

      $dialog.on('click.sbDialogInsert', clonedUse, function (e) {
        $(e.delegateTarget).addClass('media--loading');
        $btnUse.click();
      });
    }

    /**
     * Remove empty marker whenever an item is selected, or uploaded.
     */
    function upload() {
      $form.removeClass('is-empty');
    }

    /**
     * Finalizes the form actions.
     */
    function finalize() {
      $form.closest('body').addClass('sb-body');

      if ($dialog.length) {
        $form.addClass('form--dialog');
      }

      $('> div:not(.sb__header, .sb__footer)', form).addClass('sb__main');

      // Only proceed if we have selections.
      if (isEmpty()) {
        doEmpty();
        if ($dialog.length) {
          $dialog.find(clonedUse).remove();
        }
        return;
      }

      // Do dialog stuffs.
      if ($dialog.length) {
        doDialog();
      }

      $(clonedUse).text(txtUse).removeClass('visually-hidden');

      // This selection can be loaded anywhere out of Views context.
      if (!$checkBox.length) {
        return;
      }

      $form.removeClass('is-empty');
      $footer.find('.item-container').each(disableSelected);
    }

    // Events.
    $form.on('click.sbGrid', '.grid:not(.view-list--header, .was-checked)', addItem);
    $form.on('click.sbRemove', '.button--remove-js', removeItem);
    $form.on('click.sbShow', '.button--show-selection', toggleSelections);
    $form.on('click.sbUpload', '.js-form-file, .dz-clickable', upload);
    $form.on('click.sbInsert', '#edit-use-selected', Drupal.slickBrowser.loading);

    finalize();
  }

  /**
   * Attaches Slick Browser form behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickBrowserForm = {
    attach: function (context) {
      $('.form--sb', context).once('sbForm').each(sbForm);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $('.form--sb', context).removeOnce('sbForm').off('.sbGrid .sbInsert .sbRemove .sbShow .sbUpload');
      }
    }
  };

})(jQuery, Drupal);
