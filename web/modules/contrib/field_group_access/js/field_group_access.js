(function($, Drupal) {
  $(function() {
    var setFieldsets = function(disable) {
      $('fieldset.field-group-disabled').each(function() {
        $(this).find('input, select, textarea').prop('disabled', disable);
      });
    };

    $('form').each(function() {
      if ($(this).find('fieldset.field-group-disabled').length > 0) {
        $(this).submit(function() {
          setFieldsets(false);
          return true;
        });

        setFieldsets(true);
      }
    });
  });
})(jQuery, Drupal);
