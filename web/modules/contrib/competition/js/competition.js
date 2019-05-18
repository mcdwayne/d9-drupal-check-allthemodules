/**
 * @file
 * Competition behaviors.
 */

(function (document, $, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.competition = {

    attach: function (context) {

      this.adminHelper(context);

    },
    adminHelper: function (context) {

      var $checkboxRequireUser = $('form.competition-form #edit-require-user', context);
      if (!$checkboxRequireUser.length) {
        return;
      }

      var stateChange = function () {
        var $checkboxPartialSave = $('form.competition-form #edit-allow-partial-save', context);

        if ($checkboxRequireUser.is(':checked')) {
          $checkboxPartialSave
            .attr('disabled', false)
            .removeClass('disabled');
        }
        else {
          $checkboxPartialSave
            .attr('checked', false)
            .attr('disabled', true)
            .addClass('disabled');
        }
      };

      $(document)
        .on('ready', stateChange);

      $checkboxRequireUser
        .on('click', stateChange);

    }

  };

  Drupal.behaviors.competition_judging_setup = {

    attach: function (context, settings) {

      this.toggleSubforms(context);

    },

    toggleSubforms: function (context) {

      // The context might contain the form, or might be within the form (if
      // ajax injects some markup) - so make sure to select within context and
      // within form.
      var formIds = 'form#competition-judging-round-workflow, form#competition-judging-finalize-scores';
      var $forms = $(formIds);

      if (!$forms.length) {
        return;
      }

      // Action buttons - open subform.
      $('button[data-action]', context).each(function () {
        var $this = $(this);
        var $form = $this.parents(formIds);
        if ($form.length) {

          // Button's 'data-action' attribute points to a fieldset with
          // corresponding 'data-action-sub' attr.
          $this.click(function () {
            // Close any other open action subform.
            $form.find('[data-action-sub]').addClass('hidden');
            $form.find('button[data-action]').removeClass('subform-open').addClass('subform-closed');

            // Open this action subform.
            $form.find('[data-action-sub="' + $this.attr('data-action') + '"]').removeClass('hidden');
            $this.removeClass('subform-closed').addClass('subform-open');
          });

        }
      });

      // Cancel buttons - close subform.
      $('button[data-action-cancel]', context).each(function () {
        var $this = $(this);
        var $form = $this.parents(formIds);
        if ($form.length) {

          // Button's 'data-action-cancel' attribute points to a fieldset with
          // corresponding 'data-action-sub' attr.
          $this.click(function () {
            $form.find('[data-action-sub="' + $this.attr('data-action-cancel') + '"]').addClass('hidden');

            // Update class on corresponding button that opens subform.
            $form.find('[data-action="' + $this.attr('data-action-cancel') + '"]').removeClass('subform-open').addClass('subform-closed');
          });

        }
      });

    }

  };

  Drupal.behaviors.competition_judging = {

    attach: function (context, settings) {

      this.scoreDetails(context);
      this.tabs(context);
      this.modalClose(context);

    },

    tabs: function (context) {

      // Do some hacky things to make our non-standard tertiary tabs behave like core secondary tabs.
      $('nav.nav-judging > ul.tabs > li', context)
        .addClass('tabs__tab')
        .find('a.is-active')
        .parents('li')
        .addClass('is-active');

    },

    scoreDetails: function (context) {

      var $context = $(context);

      // "Close" button/link.
      var $close = null;

      if ($context.hasClass('judging-score-details-wrap')) {
        $close = $(context).find('.close');
      }
      else if ($context.find('.judging-score-details-wrap').length > 0) {
        $close = $context.find('.judging-score-details-wrap .close');
      }

      if ($close && $close.length) {
        $close.click(function () {
          // Instead of hiding the table, remove it completely. Then, any
          // future clicks will load the score table freshly - which can include
          // updated scores if they've been submitted since the last page load.
          $(this).closest('.judging-score-details-wrap').remove();
        })
        .css('cursor', 'pointer');
      }

    },

    modalClose: function (context) {

      // Listener for modal close...redirect to force UI repaint.
      //
      // @see Drupal.dialog.closeDialog()
      $(window).one('dialog:afterclose', function (event, dialog, $element) {
        if (document.location.pathname.indexOf('/admin/content/competition/judging') === 0) {
          event.stopPropagation();
          document.location.reload();

          return false;
        }
      });

    }

  };

})(document, jQuery, Drupal, drupalSettings);
