/**
 * @file
 * Provides GridStack admin utilities.
 */

(function ($, Drupal, drupalSettings, _, window) {

  'use strict';

  /**
   * GridStack form functions.
   *
   * @param {int} i
   *   The index of the current element.
   * @param {HTMLElement} form
   *   The GridStack form HTML element.
   */
  function gridStackForm(i, form) {
    var ui = Drupal.gridstack.ui;
    var $form = $(form);
    var main = '.gridstack--main';
    var framework = '[data-drupal-selector="edit-options-use-framework"]';
    var $framework = $(framework, form);
    var isNested = true;
    var $icon = $('#gridstack-icon');
    var storedIconUrl = $icon.attr('data-url') ? $icon.attr('data-url') : '';
    var gridStacks = [];
    var delta = 0;
    var windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    var mainCollections = ui.loadCollection(main);

    // Loop through main collection grids inherited by responsive grids.
    if (mainCollections.length) {
      mainCollections.each(function (collection, i) {
        if (i > delta) {
          delta = i;
        }
      });
    }

    /**
     * GridStack layout methods applied to the top level GridStack element.
     *
     * @param {int} i
     *   The index of the current element.
     * @param {HTMLElement} root
     *   The GridStack HTML element.
     */
    function gridStackRoot(i, root) {
      var $root = $(root);
      var data = {
        el: root,
        collection: ui.loadCollection(root),
        isNested: isNested,
        options: {
          breakpoint: $root.data('breakpoint'),
          icon: $form.data('icon'),
          id: $root.attr('id'),
          isNested: isNested,
          storage: $root.data('storage'),
          useCss: $root.data('framework') === 1,
          nestedStorage: $root.data('nestedStorage'),
          noMargin: $('#edit-options-settings-nomargin').prop('checked')
        }
      };

      var gridStack = ui.loadGridStack(data);
      var isMain = gridStack.$el.hasClass('gridstack--main');

      /**
       * Reacts on button click events.
       *
       * @param {jQuery.Event} e
       *   The event triggered by a `click` event.
       *
       * @return {mixed|bool}
       *   Returns false if the current target is not this, else default.
       */
      function onSaveIcon(e) {
        e.preventDefault();

        if (e.currentTarget !== this) {
          return false;
        }

        var $btn = $(this);
        var message = $btn.data('message');

        if (isMain) {
          gridStack.collection.trigger('gridstack:main:' + message);

          Drupal.gridstack.icon.build(form, true);
        }
      }

      // Collect GridStack instances for aggregated CRUD.
      gridStacks.push(gridStack);

      // Render the GridStack instance.
      gridStack.render();

      if (gridStack.$el.hasClass('gridstack--main')) {
        $form.off('click.gs.save').on('click.gs.save', '.btn--main.btn--save', onSaveIcon);
      }
    }

    /**
     * Build column selector.
     */
    function onSelectColumn() {
      var dataGridStack;

      $(this).change(function (e) {
        if (e.target === this) {
          var $el = $(this);
          var $target = $($el.data('target'));

          if ($target.length) {
            dataGridStack = $target.data('gridstack');
            if (!_.isUndefined(dataGridStack)) {
              var c = $el.val() ? $el.val() : $target.data('currentColumn');

              dataGridStack.setGridWidth(c);
            }
          }
        }
      }).change();
    }

    /**
     * Build width selector which affects GridStack width for correct preview.
     *
     * @param {int} i
     *   The index of the current element.
     * @param {HTMLElement} el
     *   The width input HTML element.
     */
    function onInputWidth(i, el) {
      var $el = $(el);

      var updateWidth = function (input) {
        var gs = input.data('target');
        var $target = $(gs);
        var $subPreview = $target.closest('.gridstack-preview--sub');

        if ($target.length) {
          var value = input.val();
          var w = value ? parseInt(value) : parseInt($target.data('responsiveWidth'));
          var width = w < 600 ? 600 : w;

          if (value !== '') {
            $target.css({
              width: width,
              maxWidth: width > windowWidth ? '' : width
            });
          }

          $subPreview[value === '' ? 'addClass' : 'removeClass']('form-disabled');
        }
      };

      updateWidth($el);

      $el.on('blur', function (e) {
        if (e.target === this) {
          var input = $(this);
          updateWidth(input);
        }
      });
    }

    /**
     * Sets the framework environment.
     */
    function setFramework() {
      $form[$framework.prop('checked') ? 'addClass' : 'removeClass']('is-framework');
    }

    /**
     * Reacts on form submission.
     *
     * @param {jQuery.Event} e
     *   The event triggered by a `click` event.
     */
    function onFormSubmit(e) {
      $form.addClass('is-saving');

      // Some stored values dependent on :visible pseudo will not store with
      // CSS display none, hence force them visible.
      $('.vertical-tabs__menu li', $form).removeClass('selected');
      $('.vertical-tabs__menu li:last a', $form).click();
      $('.vertical-tabs__pane', $form).css('display', 'block').addClass('visually-hidden');
      $('.vertical-tabs__pane:last', $form).removeClass('visually-hidden');

      // Failsafe to generate icon if "Save & continue" button is not hit.
      $('.btn--gridstack[data-message="save"]').each(function () {
        $(this).trigger('click');
      });
    }

    /**
     * Reacts on button click events.
     *
     * @param {jQuery.Event} e
     *   The event triggered by a `click` event.
     *
     * @return {mixed|bool}
     *   Returns false if the current target is not this, else default.
     */
    function onBoxMultiple(e) {
      e.preventDefault();

      if (e.currentTarget !== this) {
        return false;
      }

      var $btn = $(this);
      var boxId = $btn.data('id');
      var pid = $btn.data('pid');
      var message = $btn.data('message');
      var type = $btn.data('type') || 'root';
      var id = pid || boxId;

      _.each(gridStacks, function (gridStack, i) {
        var box = gridStack.getCurrentBox(id);

        // Do not use direct child selector (>) to respect nested boxes.
        var $box = $('.box[data-id="' + boxId + '"]', gridStack.$el);

        gridStack.collection.trigger('gridstack:' + type + ':' + message, e, box, $box);
      });
    }

    /**
     * Reacts on button click events.
     *
     * @param {jQuery.Event} e
     *   The event triggered by a `click` event.
     *
     * @return {mixed|bool}
     *   Returns false if the current target is not this, else default.
     */
    function onAddMultiple(e) {
      e.preventDefault();

      if (e.currentTarget !== this) {
        return false;
      }

      delta++;

      var index = (delta + 1);
      var box = new Drupal.gridstack.models.Box({
        index: index
      });

      _.each(gridStacks, function (gridStack, i) {
        gridStack.collection.add(box);
      });
    }

    // Loop through each GridStack root instance, not nested one.
    $('.gridstack--root', form).each(gridStackRoot);

    // Check if using CSS framework, or GridStack JS.
    setFramework();

    // Display icon if exists at public, or MODULE_NAME/images, directory.
    if (storedIconUrl) {
      var date = new Date();
      $('#gridstack-screenshot', form).html('<img src="' + storedIconUrl + '?rand=' + date.getTime() + '" alt="Icon" />');
    }

    // Run actions.
    $form.find('.form-text--width').each(onInputWidth);
    $form.find('.form-select--column').each(onSelectColumn);

    $form.off('click.gs.framework').on('click.gs.framework', framework, setFramework);

    $form.off('click.gs.box').on('click.gs.box', '.btn--box', onBoxMultiple);
    $form.off('click.gs.add').on('click.gs.add', '.btn--main.btn--add', onAddMultiple);

    $form.on('submit', onFormSubmit);
  }

  /**
   * Attaches gridstack behavior to HTML element .form--gridstack.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.gridStackAdmin = {
    attach: function (context) {
      $('.form--gridstack', context).once('form-gridstack').each(gridStackForm);
    }
  };

})(jQuery, Drupal, drupalSettings, _, this);
