(function ($) {

  'use strict';

  $(document).ready(function () {
    $('form').each(function () {
      var $language_switch = $('select.language-switch-field');
      // Support only one language widget per form.
      if ($language_switch.length > 1) {
        alert('Currently no more than one language switch widgets are supported on the same form.');
        throw new Error('Currently no more than one language switch widgets are supported on the same form.');
      }

      // If there is a language switch widget then add the on change event
      // listener and on change modify the form action to point to the newly
      // selected language.
      if ($language_switch.length === 1) {
        // Keep track of the previous value, which will be used later for search
        // and replace.
        $language_switch.data('previous-val', $language_switch.val());
        $language_switch.on('change', function() {
          var previous_val = $language_switch.data('previous-val');
          var new_val = $language_switch.val();
          var $form = $language_switch.closest('form');
          var action = $form.attr('action');
          // Update the action URl to point to the new translation.
          action = action.replace('language_content_entity=' + previous_val, 'language_content_entity=' + new_val);
          $form.attr('action', action);
          // Update the select list value to the newly selected one.
          $language_switch.data('previous-val', new_val);
        });
      }
    });
  });

})(jQuery);
