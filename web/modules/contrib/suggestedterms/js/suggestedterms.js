(function ($) {

  "use strict";

  Drupal.behaviors.suggestedterms = {
    attach: function (context, settings) {
    // Get all the suggestedterm links
    $('a.suggestedterm').each ( function() {
      // Change the path to an anchor.
      $(this).attr('href', '#');

      // Add to/remove from field on click of term item.
      $(this).bind('click', function(event) {
          event.preventDefault();
          // Get the form item's input object and make an array of its text.
          var input = $(this).closest('.form-type-textfield').find('input');
          var input_array = $.map(input.val().split(','), $.trim);
          // Get the text of the term item that was clicked.
          var text = $(this).text();
          toggleTerms(this, text, input_array, input);
        }); // end bind

        $(this).closest('.form-item').find('input').bind('keydown', function(event) {
          // @todo: make list of terms respond to manual changes to the field.
        });

      }); // end span.suggestedterm

      function toggleTerms(item, text, input_array, input) {
        // If it's not already in the input field, add it.
        if ($.inArray(text, input_array) < 0) {
          // If it's not the first item in the field, prefix with comma.
          if (input.val().length > 0) {
            input.val(input.val() + ', ');
          }
          // Append the value to the field value.
          input.val(input.val() + text);
          // Mark it as removed from the available options.
          $(item).addClass('remove');
        }
        else {
          // Remove the clicked item from the input array.
          input_array.splice( $.inArray(text, input_array), 1 );
          // Convert the array to a comma-separated string.
          var input_string = input_array.join(', ');
          // Set the input value to the new string, sans clicked item.
          input.val(input_string);
          // Put it back in the list of available options.
          $(item).removeClass('remove');
        }
      }
    } // end attach
  }; // end Drupal.behaviors.suggestedterms
})(jQuery);
