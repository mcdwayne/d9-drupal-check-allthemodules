/**
 * @file
 * EV chooser CKEditor impl.
 */

(function($) {

  'use strict';

  var width = 700,
    height = 460;

  CKEDITOR.plugins.add('evchooser', {
    init: function(editor) {

      editor.addCommand('evchooser', new CKEDITOR.dialogCommand('evchooserDialog'));

      editor.ui.addButton('evchooser_button', {
        label: 'Ensemble Video',
        icon: this.path + 'icons/evchooser.png',
        command: 'evchooser'
      });
    }
  });

  CKEDITOR.dialog.add('evchooserDialog', function(editor) {
    return {
      title: 'Ensemble Video Chooser',
      minWidth: width,
      minHeight: height,
      contents: [{
        id: 'main',
        label: 'Chooser',
        elements: [{
          type: 'html',
          html: '<input name="editorId" type="hidden" value="' + editor.name + '" />' +
            '<div class="evchooserFrameWrap"></div>'
        }]
      }],
      buttons: [
        CKEDITOR.dialog.cancelButton
      ]
    };
  });

  CKEDITOR.on('dialogDefinition', function(e) {
    var dialogName = e.data.name,
      dialog = e.data.definition.dialog,
      editor = dialog.getParentEditor(),
      $el = $(dialog.getElement().$);
    if (dialogName === 'evchooserDialog') {
      // Add a unique class so we can find the dialog from within our result frame
      // in order to trigger custom done event.
      $el.addClass('evchooserDialog_' + editor.name);
      dialog.on('show', function() {
        $('.evchooserFrameWrap', $el).append('<iframe id="evchooserFrame_' + editor.id + '" class="evchooserFrame" src="' + drupalSettings.path.baseUrl + 'evchooser/launch" style="width:' + width + 'px;height:' + height + 'px;"/>');
      });
      dialog.on('hide', function() {
        $('.evchooserFrameWrap', $el).empty();
      });
      dialog.on('resize', function(e) {
        $('.evchooserFrameWrap iframe', $el).css({
          height: e.data.height + 'px',
          width: e.data.width + 'px'
        });
      });
      $el.on('evchooserDone', function() {
        dialog.hide();
      });
    }
  });

})(jQuery);
