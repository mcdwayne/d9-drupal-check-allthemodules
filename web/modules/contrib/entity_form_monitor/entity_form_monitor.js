/**
 * @file
 * Provides JavaScript for Entity Form Monitor.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Test
   *
   * @namespace
   */
  Drupal.entityFormMonitor = Drupal.entityFormMonitor || {

    /**
     * The update monitor.
     *
     * This will be set in Drupal.entityFormMonitor.startMonitoring().
     */
    timer: null,

    /**
     * Start monitoring the entity forms.
     *
     * @name Drupal.entityFormMonitor.startMonitoring
     */
    startMonitoring: function(interval) {
      // Start the update poller. We only need to do this once even if a
      // later AJAX request adds another entity form, because the
      // Drupal.entityFormMonitor.checkForms() method looks globally
      // at all current forms on the page.
      if (this.timer === null) {
        this.timer = setInterval(this.checkForm, interval);
      }
    },

    /**
     * Stop monitoring the entity forms.
     *
     * @name Drupal.entityFormMonitor.stopMonitoring
     */
    stopMonitoring: function() {
      clearInterval(this.timer);
    },

    /**
     * Mark an entity form as changed.
     *
     * @name Drupal.entityFormMonitor.markFormChanged
     *
     * @param {jQuery.Event} event
     *   The event triggered, most likely a `change` event.
     */
    markFormChanged: function (event) {
      // Handle CKEditor change events.
      if (typeof event.editor !== "undefined" && typeof event.target === "undefined") {
        event.target = event.editor.element.$;
      }

      $entityForm = $(event.target).parents('[data-entity-form-monitor]').first();
      if ($entityForm.length) {
        $entityForm.data('entity-form-changed', true);
      }
    },

    /**
     * Checks if any entity forms have been changed.
     *
     * @name Drupal.entityFormMonitor.isFormChanged
     */
    isFormChanged: function () {
      return $('[data-entity-form-monitor]:data(entity-form-changed)').length > 0;
    },

    /**
     * Invalidates the current form and either reloads the page or shows a modal.
     *
     * @name Drupal.entityFormMonitor.invalidateForm
     */
    invalidateForm: function () {
      // @todo Convert this dialog and message to something that is injected via AJAX commands.
      if (this.isFormChanged()) {
        var $dialog = $('<div id="entity-form-update-changed-dialog" title="Form out of date">This form is out of date and cannot be submitted. Would you like to reload the form? Any changes you may have saved will be lost when the page reloads.</div>');
        $('body').append($dialog);
        $dialog.dialog({
          resizable: false,
          height: "auto",
          width: 400,
          modal: true,
          buttons: {
            "Reload form": function () {
              location.reload();
            },
            Cancel: function () {
              // Display a warning message on the form.
              var $warning = $('<div class="messages messages--error">The form has become outdated. Copy any unsaved work in the form below and then <a href="' + window.location + '">reload this page</a>.</div>');
              $('form').first().before($warning);
              // Close and remove the dialog.
              $(this).dialog("close");
              $dialog.remove();
            }
          }
        });
      }
      else {
        location.reload();
      }
    },

    /**
     * Monitor updates to all entity forms on the current page.
     *
     * @name Drupal.entityFormMonitor.monitorForms
     */
    checkForm: function () {
      var $entityForms = $('[data-entity-form-monitor]');
      if ($entityForms.length) {
        // Gather all the entity IDs and last changed timestamps.
        var entityData = [];
        $entityForms.each(function() {
          // This attribute is a combination of the entity type and ID
          // in the format of entity-type:entity-id
          var entityId = $(this).data('entity-form-monitor');
          entityData[entityId] = $(this).data('entity-last-changed');
        }).get();

        // Send the AJAX request to get the current entity updated timestamps.
        $.ajax({
          url: drupalSettings.path.baseUrl + 'entity-form-monitor',
          data: {
            'entity_ids': Object.keys(entityData)
          },
          type: 'POST',
          error: function () {
            // If we encountered an error, stop monitoring.
            this.stopMonitoring();
          },
          success: function (updatedData) {
            $.each(updatedData, function(entityId, lastChanged) {
              // A false value means the entity was deleted.
              if (lastChanged === false || lastChanged > entityData[entityId]) {
                Drupal.entityFormMonitor.stopMonitoring();
                Drupal.entityFormMonitor.invalidateForm();
                return false;
              }
            });
          }
        });
      }
    }

  };

  /**
   * Attach behaviors to monitor entity forms.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches triggers.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches triggers.
   */
  Drupal.behaviors.entityFormMonitor = {
    attach: function (context, settings) {
      var $entityForms = $('[data-entity-form-monitor]');
      if ($entityForms.length) {
        // Add a change handler that will help us determine if any inputs
        // inside the entity forms have changed values.
        $entityForms.find(':input, [contenteditable="true"]')
          // Filter out buttons
          .not('button, input[type="button"], input[type="submit"], input[type="reset"]')
          .once('entity-form-input-monitor')
          .on('change input', Drupal.entityFormMonitor.markFormChanged);
        // Start the update monitor. We only need to do this once even if a
        // later AJAX request adds another entity form, because the
        // Drupal.entityFormUpdateCheck.checkForms() method looks globally
        // at all current forms on the page.
        var interval = settings.entityFormMonitor.interval * 1000;
        Drupal.entityFormMonitor.startMonitoring(interval);

        // Add change handlers to any CKEditor instances.
        if (typeof CKEDITOR !== "undefined") {
          CKEDITOR.on("instanceCreated", function (event) {
            event.editor.on("change", Drupal.entityFormMonitor.markFormChanged);
          });
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
