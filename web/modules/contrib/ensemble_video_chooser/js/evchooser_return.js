/**
 * @file
 * Updates the current editor instance with request content on return trip.
 */

(function($, p$) {

  $(document).ready(function() {

    // Get the content parameter returned by chooser.
    var content = drupalSettings.path.currentQuery.content;

    // This frame's parent is our wrapper, whose parent contains our custom html.
    // Do this in the context of the parent's jquery.
    var p$container = p$('#' + window.frameElement.id).parent().parent();

    // Retrieve the editor id we stashed so we can work with the proper editor instance.
    var editorId = p$('input[name="editorId"]', p$container).val();
    var editor = window.parent.CKEDITOR.instances[editorId];

    // Now that we have the editor instance, insert the returned content.
    editor.insertHtml(content);

    // Trigger custom chooserDone event on our dialog element in parent context.
    // This should close our dialog.
    p$('.evchooserDialog_' + editorId).trigger('evchooserDone');
  });

  // Load our jquery and parent's jquery.
}(jQuery, window.parent.jQuery));
