/**
 * @file
 * Provides behaviors for Field UI AJAX.
 */

(function ($, window, Drupal, drupalSettings) {

  'use strict';
  var messagesDiv;

  /**
   * Detaches the Ajax behavior to each Ajax link having "use-ajax-once" class.
   * Makes links toggle visibility of content on the page after they loaded the
   * content through AJAX.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.fieldUiAJAX = {
    attach: function (context, settings) {
      // Create only one messages div element.
      if (!messagesDiv) {
        messagesDiv = document.createElement('div');
        messagesDiv.id = 'field-ui-messages';
        messagesDiv.className = 'field-ui-messages-hidden';
        document.body.appendChild(messagesDiv);
      }
      messagesDiv.addEventListener('transitionend', hideMessages);
      var $context = $(context);
      // For ajax links that have "use-ajax-once" we want to remove the ajax
      // behavior after the first click.
      $context.find('.use-ajax-once').once('use-ajax-once').each(function () {
        $(this).on('click.use_ajax_once', function () {
          var element = $(this);
          element.removeClass('use-ajax use-ajax-once').off('.ajax .use_ajax_once');
          var hide = element.data('field-ui-hide');
          var show = element.data('field-ui-show');
          if (hide && show) {
            // If it has both a 'hide' and a 'show' we need to make the link a
            // toggle. We add the class here and the behavior will be attached
            // after this each loop is finished.
            element.addClass('js-field-ui-toggle');
          }
          if (show) {
            processAditionalLinks(show);
          }
        });
      });

      // Behavior for toggle links. We don't use context here because the links
      // can be outside the context.
      $('.js-field-ui-toggle').once('js-field-ui-toggle').each(function () {
        var hide = $(this).data('field-ui-hide');
        var show = $(this).data('field-ui-show');
        $(this).on('click.field_ui_toggle', function (event) {
          $(hide).addClass('js-field-ui-hidden');
          $(show).removeClass('js-field-ui-hidden');
          event.preventDefault();
        });
      });

      // Behavior for tabs.
      var nav = $('nav .secondary');
      var span = $('span.visually-hidden', nav);
      $context.find('.js-field-ui-tabs').once('js-field-ui-tabs').each(function () {
        $(this).on('click.field_ui_tabs', function (event) {
          var element = $(this);
          $('.is-active', nav).removeClass('is-active');
          span.appendTo(element);
          element.addClass('is-active').parent().addClass('is-active');
          event.preventDefault();
        });
      });

      function processAditionalLinks(type) {
        // If we have multiple links that should trigger this, process them
        // all.
        $('.use-ajax-once' + type + '-trigger').each(function () {
          var other_element = $(this);
          other_element.removeClass('use-ajax use-ajax-once').off('.ajax .use_ajax_once');
          var hide = other_element.data('field-ui-hide');
          var show = other_element.data('field-ui-show');
          if (hide && show) {
            other_element.addClass('js-field-ui-toggle');
          }
        });
      }
    },

    detach: function (context, settings) {
    }
  };

  function hideMessages() {
    // Wait for the next frame so we know all the style changes have taken hold.
    requestAnimationFrame(function () {
      // Switch off messages.
      messagesDiv.classList.remove('field-ui-messages-show');
    });
  }

  /**
   * Attaches the Ajax behavior to each Ajax form element. We overwrite the one
   * provided by core so we can namespace the click events.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.AJAX.attach = function (context, settings) {

    function loadAjaxBehavior(base) {
      var element_settings = settings.ajax[base];
      if (typeof element_settings.selector === 'undefined') {
        element_settings.selector = '#' + base;
      }
      $(element_settings.selector).once('drupal-ajax').each(function () {
        element_settings.element = this;
        element_settings.base = base;
        Drupal.ajax(element_settings);
      });
    }

    // Load all Ajax behaviors specified in the settings.
    for (var base in settings.ajax) {
      if (settings.ajax.hasOwnProperty(base)) {
        loadAjaxBehavior(base);
      }
    }

    // Bind Ajax behaviors to all items showing the class.
    $('.use-ajax').once('ajax').each(function () {
      var element_settings = {};
      // Clicked links look better with the throbber than the progress bar.
      element_settings.progress = {type: 'throbber'};

      // For anchor tags, these will go to the target of the anchor rather
      // than the usual location.
      if ($(this).attr('href')) {
        element_settings.url = $(this).attr('href');
        element_settings.event = 'click.ajax';
      }
      element_settings.dialogType = $(this).data('dialog-type');
      element_settings.dialog = $(this).data('dialog-options');
      element_settings.base = $(this).attr('id');
      element_settings.element = this;
      Drupal.ajax(element_settings);
    });

    // This class means to submit the form to the action using Ajax.
    $('.use-ajax-submit').once('ajax').each(function () {
      var element_settings = {};

      // Ajax submits specified in this manner automatically submit to the
      // normal form action.
      element_settings.url = $(this.form).attr('action');
      // Form submit button clicks need to tell the form what was clicked so
      // it gets passed in the POST request.
      element_settings.setClick = true;
      // Form buttons use the 'click' event rather than mousedown.
      element_settings.event = 'click.ajax';
      // Clicked form buttons look better with the throbber than the progress
      // bar.
      element_settings.progress = {type: 'throbber'};
      element_settings.base = $(this).attr('id');
      element_settings.element = this;

      Drupal.ajax(element_settings);
    });
  };

})(jQuery, this, Drupal, drupalSettings);
