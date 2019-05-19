/**
 * @file
 * Twig debug JavaScript behaviors.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  jQuery(function () {
    // Process each script tag and make a visible tag you can click to get more
    // information about the theme hook.
    jQuery('script[type="twig-debug-info"]').each(function () {
      var data = JSON.parse(this.innerHTML);
      var debugTag = $('<code class="twig-debug">{{debug}}</code>');
      debugTag.data('twig-debug-data', data);

      $(this).after(debugTag);
    });

    // Add a toggle handle to enable/disable Twig debugging.
    var debugToggle = $('<label class="twig-debug-toggle"><input type="checkbox" />Display Twig debugging information</label>');

    // When the toggle is toggled, toggle the visible class on the body.
    debugToggle.find('input').change(function () {
      $('body').toggleClass('twig-debug-visible', $(this).is(':checked'));
    });

    $('body').append(debugToggle);

    // Whenever a debug tag is clicked, open a dialog with the debug data.
    $(document).on('click', '.twig-debug', function () {
      var data = $(this).data('twig-debug-data');

      if (data) {
        var dialogContent = $('<div />');

        // Show the current template used:
        dialogContent.append('<p>Current template: <em>' + data.file_name + '</p>');

        // Mkae a list of the file name suggestions.
        if (data.file_name_suggestions) {
          dialogContent.append('<h4>File name suggestions</h4>');

          var suggestionList = $('<ul class="filename-suggestion-list" />');

          jQuery.each(data.file_name_suggestions, function () {
            suggestionList.append('<li>' + this + '</li>');
          });

          suggestionList.appendTo(dialogContent);
        }

        dialogContent.dialog({
          title: data.call,
          width: '80%'
        });
      }
    });
  });
}(jQuery, Drupal, drupalSettings));
