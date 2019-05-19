(function ($) {

  'use strict';

  $(document).ready(function () {

    // On check "All", change all checkboxes
    $('input#edit-config-fields-check-all').change(function () {

      $('#edit-config-fields .form-checkbox').prop('checked', $(this).prop('checked')); // Change all checkbox checked status

    });

  });

})(jQuery);
