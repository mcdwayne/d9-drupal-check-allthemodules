(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.stacks_finalize = {
    attach: function (context, settings) {
      var delta = drupalSettings.stacks.finalize.delta,
        widget_instance_id = drupalSettings.stacks.finalize.widget_instance_id,
        $completed_message = $('#completed_message');

      // Update the hidden input.
      $('#widget-instance-' + delta + ' input').once('stacks_finalize_update').val(widget_instance_id);

      // Trigger new row button.
      if ($completed_message.length > 0) {
        $('#widget-form-' + delta).once('stacks_finalize_message').each(function () {
          var $object = $(this);
          var parent = $object.closest('.field--type-stacks-type');
          $('.field-add-more-submit', parent).mousedown();
          $completed_message.html(Drupal.t('Loading...'));
        });

        $('#edit-actions').find('input').removeAttr('disabled').removeClass('is-disabled');
        $('a.remove-widget, a.edit-widget').show();
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
