/**
 * @file
 * Handles automatic LTI launch form submission.
 */

(function($) {
  $(document).ready(function() {
    var $form = $('#launchForm'),
      action = $form.attr('action');
    if (action) {
      $form.submit();
    }
    else {
      window.alert('Missing Launch Url! Make sure this module is properly configured.');
    }
  });
}(jQuery));
