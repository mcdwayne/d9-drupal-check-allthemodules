/**
 * @file
 */

(function ($) {
  Drupal.behaviors.ebourgognetf = {
    attach: function (context, settings) {

      /****** On the configuration page .******/
      // If user select a teleform in the list.
      $("#edit-formlist").change(function () {
        // Retrieve url for the selected form.
        var linkUrl = $("#edit-formlist option:selected").val();

        // Display the textfield to enter the link label.
                $("#linkUrl").html(linkUrl);
      });
    }
  };
}(jQuery));
