
(function ($, _, Backbone, Drupal, drupalSettings) {

  var changedForm = false;

  /**
   * Override tabledrag message.
   */
  Drupal.theme.tableDragChangedWarning = function () {
      return '<div class="tabledrag-changed-warning messages messages--warning" role="alert">' + Drupal.theme('tableDragChangedMarker') + ' ' + Drupal.t('Save the page to update your widget placement.') + '</div>';
  };

  /**
   * Ajax command to attach changed events on form elements
   */
  Drupal.AjaxCommands.prototype.attachOnChangeEvents = function(ajax, response, status) {
    // Detect form changes
    $(response.selector).once('cancel_action').on("change", "input, textarea, select", function() {
      $(this).closest('.widget-form').attr('data-haschanged', 'true');
    });
  }

  /**
   * Cancel widget ajax command.
   */
  Drupal.AjaxCommands.prototype.cancelWidget = function(ajax, response, status) {

    var proceed = true;
    // Detect CKEditor text changes
    var cke_textareas = $(response.selector).find('textarea[data-editor-value-is-changed="true"]');

    if ($(response.selector).attr('data-haschanged') == 'true' || cke_textareas.length > 0) {
      if (confirm('The changes done in this widget will be lost. Confirm to discard the changes?') == false) {
        proceed = false;
      }
    }

    if (proceed) {
      var $object = $(response.selector);
      var parent = $object.closest('.field--type-stacks-type');

      $('.field-add-more-submit', parent).mousedown();
      $('#edit-actions input').removeAttr('disabled');
      $('#edit-actions input').removeClass('is-disabled');
    }
  }


  /**
   * Replace widget ajax command.
   */
  Drupal.AjaxCommands.prototype.replaceWidget = function (ajax, response, status) {
    if (typeof response.selector !== "undefined") {
      var scroll = $(window).scrollTop();
      var $object = $(response.selector);
      $object.parents('.field__item .contextual-region').replaceWith(response.data);


      Drupal.behaviors.contextual.attach(document);

      window.setTimeout(function () {
        // Prevent scrolling to top
        $('html, body').animate({
          scrollTop: scroll,
        }, 0);

        // Adding custom animation class to new element
        $(response.selector).parent().addClass('is-edited');

        setTimeout(function () {
          $(response.selector).parent().removeClass('is-edited');
        }, 2000);
        // for (var i = 0; i < 3; i++) {
        //   $(response.selector).parent()
        //     .animate({ opacity: 0.5 }, 200)
        //     .animate({ opacity: 1.0 }, 200);
        // }

        Drupal.behaviors.AJAX.attach(response.selector, drupalSettings);
      });
    }
  };

    /**
     * Add new command to undo widget deletion.
     */
    Drupal.AjaxCommands.prototype.undoWidgetDelete = function (ajax, response, status) {
      var $object = $(response.selector);
      $object.find('input').val(response.value);
    };


  Drupal.behaviors.stacks_node = {
    attach: function (context, settings) {

      $('a.edit-widget, a.add-widget').once('populate_theme').on('mouseup', function(e) {
        $('a.remove-widget, a.edit-widget, a.add-widget').hide();
      });

      $('a.remove-widget').once('populate_theme').on('mousedown', function(e) {
        var widgetName = $(this).closest('.widget-form').find('h2:first').html();
        var result = confirm('Are you sure you want to delete \'' + widgetName + '\'?');
        if (result) {
          $(this).trigger('click');
        }
        else {
          e.preventDefault();
          e.stopPropagation();
        }
      });

      $('.existing_stacks_pager ul.pager__items li.pager__item > a').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var page = $(this).data('page-number');
        $('input[data-drupal-selector="edit-table-pager"]').val(page).trigger('change');
      });

      // Remove html for the required stacks that can't be moved.
      $('.required_locked').once('required_locked').each(function () {
        var $object = $(this).closest('.draggable');
        $object.removeClass('draggable');
        $object.children('.field-multiple-drag').html('<div class="locked_img"></div>').addClass('locked');
      });

      // Select the radio when row is clicked.
      $('.existing-widgets-table tr', context).each(function() {
        $(this).click(function() {
          var $this = $(this);

          // Is this an empty row?
          if ($('> td.empty', $this).length > 0) {
            return;
          }

          $this.siblings().removeClass('selected-row');
          $this.addClass('selected-row');

          // Check the checkbox.
          $('input[type=radio]', $this).prop('checked', true).trigger('change');
          
          // Make sure that a checkbox was actually checked.
          if ($('input[type=radio]:checked', $this).length < 1) {
            // They didn't actually check a checkbox!
            return;
          }

          // Enable the submit button.
          $this.closest('.form-wrapper').find('input[data-drupal-selector="edit-finishexisting"]').removeAttr('disabled');
        });
      });

      // Adding handlers for front-end editor dialogs
      $('#edit-widget-dialog').on("dialogopen", function(event, ui) {
        var overlayIndex = $(this).css('z-index');
        $(this).parent().after('<div class="ui-widget-overlay ui-front ui-widget-editor"></div>');
        $('.ui-widget-editor').css('z-index', (overlayIndex - 1));
      });

      // Adding handlers for front-end editor dialogs
      $('#edit-widget-dialog').on("dialogbeforeclose", function(event, ui) {
        $('.ui-widget-editor').remove();
      });

      $('.modal-stacks-save').once('stacks').click( function(e) {
        // Prevents stacks confirmation messages to be loaded more than once.
        e.preventDefault();
        $('#edit-widget-dialog .js-form-submit').once('stacks').trigger('click');

        //TODO: add this modal depending on the widget_times_used from the widget information (check $this->entity) on WidgetEntityForm.php
        /*
        if (!$('#modal-stacks-confirmation').length) {
          var $confirmationWrapper = $('<div id="modal-stacks-confirmation">'
            + Drupal.t('Saving this widget will alter all its appearances in the website. Continue?')
            + '</div>').appendTo('body');
          Drupal.dialog($confirmationWrapper, {
            title: Drupal.t('Warning'),
            buttons: [
              {
                text: Drupal.t('Yes'),
                click: function () {
                  $(this).dialog('close');
                  $('#edit-widget-dialog .js-form-submit').once('stacks').trigger('click');
                }
              },
              {
                text: Drupal.t('No'),
                click: function() {
                  $(this).dialog('close');
                }
              }
            ]
          }).showModal();
        } */
      });

      // Clear Widget titles depending on the "Reusable widget" checkbox
      $('input[data-drupal-selector="edit-reusable"]').mousedown(function(){
        var $field_name = $(this).closest('.form-wrapper');
        $field_name.find('input[data-drupal-selector="edit-widget-name"]').val('');
      });

    }
  };
})(jQuery, _, Backbone, Drupal, drupalSettings);

