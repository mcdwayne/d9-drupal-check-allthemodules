/**
 * @file
 * Display 'Webform' listbox if 'Webform submit' event has been selected.
 */

(($, Drupal) => {
  Drupal.behaviors.rulesWebform = {
    attach(context) {
      // Hide 'webform id' element.
      $("[for='edit-webform-id']").hide(1);
      $('#edit-webform-id').hide(1);
      $(context).find('#edit-events-0-event-name').once().each((i, obj) => {
        $(obj).change(() => {
          // Events supported by the module.
          var webform_events = [
            'webform_submit',
            'updating_submission',
            'deleting_submission',
            'viewing_submission',
          ];

          if (webform_events.includes(obj.value)) {
            $("[for='edit-webform-id']").show(1);
            $('#edit-webform-id').show(1);
            $("[for='edit-webform-id']").addClass('js-form-required form-required');
            $('#edit-webform-id').attr('required', 'required');
            $('#edit-webform-id').attr('aria-required', true);
          }
          else {
            $("[for='edit-webform-id']").hide(1);
            $('#edit-webform-id').hide(1);
            $('#edit-webform-id').removeAttr('required');
            $('#edit-webform-id').removeAttr('aria-required');
          }
        });
      });
    },
  };
})(jQuery, Drupal);
