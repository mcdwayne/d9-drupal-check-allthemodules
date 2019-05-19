(function($, Drupal, drupalSettings) {
  $(function() {
    var disableFieldsets = function(disable) {
      $('fieldset.field-group-disabled').each(function() {
        if (disable) {
          $(this).find('input, select, textarea').attr('disabled', 'disabled');
          $(this).attr('disabled', 'disabled');
        } 
        else {
          $(this).find('input, select, textarea').removeAttr('disabled');
          $(this).removeAttr('disabled');
        }

        $(this).find('input, select, textarea').addClass(drupalSettings.workflow_field_groups.disabled_class);
        $(this).addClass(drupalSettings.workflow_field_groups.disabled_class);
      });
    };

    $('form').each(function() {
      if ($(this).find('fieldset.field-group-disabled').length > 0) {
        disableFieldsets(true);
        $(this).submit(function() {
          disableFieldsets(false);
        });
      }
    });
  });
})(jQuery, Drupal, drupalSettings);
