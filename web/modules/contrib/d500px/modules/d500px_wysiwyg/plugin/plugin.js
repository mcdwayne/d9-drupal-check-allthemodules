/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($) {

  /**
   * A CKEditor plugin for 500px filter
   */
  CKEDITOR.plugins.add('d500px_wysiwyg', {
    hidpi: true,

    /**
     * Set the plugin modes.
     */
    modes: {
      wysiwyg: 1
    },

    /**
     * Define the plugin requirements.
     */
    requires: 'widget',

    /**
     * Allow undo actions.
     */
    canUndo: true,

    /**
     * Init the plugin.
     */
    init: function (editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
    },

    /**
     * Add the command to the editor.
     */
    addCommand: function (editor) {
      var self = this;

      var modalSaveWrapper = function (values) {
        //editor.fire('saveSnapshot');
        //console.log('hello');
        self.modalSave(editor, values);
        //editor.fire('saveSnapshot');
      };

      editor.addCommand('d500px_wysiwyg_add_command', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the d500px_add_photo widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};

          // TODO Fix the previous selection.
          // self.getSelectedLink(editor);
          // if (editor.widgets.focused && editor.widgets.focused.name == 'd500px_wysiwyg') {
          //  existingValues = editor.widgets.focused.data.json;
          // }

          var dialogSettings = {
            title: Drupal.t('Add 500px Photo'),
            dialogClass: 'd500px-wysiwyg-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('d500px-wysiwyg/dialog/' + editor.config.drupal.format), existingValues, modalSaveWrapper, dialogSettings);
        }
      });
    },

    /**
     * Register the widget.
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('d500px_wysiwyg');
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      var photoid = values['d500px_photo']['photoid'];
      var imagesize = values['d500px_photo']['imagesize'];
      var d500px_token = "[d500px photoid=" + photoid + " imagesize=" + imagesize + "]";
      editor.insertHtml(d500px_token);
    },

    /**
    *   The selected link element, or null.
    */
    getSelectedLink: function (editor) {
      var selection = editor.getSelection();
      var range = selection.getRanges();
      // TODO Fix the token selection.
      console.log(range);
      /*
      if (range) {

      }*/
      return null;
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('d500px_wysiwyg_add_button', {
        label: Drupal.t('Add 500px Photo'),
        command: 'd500px_wysiwyg_add_command',
        icon: this.path + '/icon.png'
      });
    }
  });

})(jQuery);
