/**
 * @file
 * Drupal Apester Plugin.
 */

(function ($, Drupal, CKEDITOR) {

  CKEDITOR.plugins.add('apester', {
    icons: 'apester',
    modes: {
      wysiwyg: 1
    },
    requires: 'widget',
    canUndo: true,

    init: function(editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
    },

    addCommand: function(editor) {
      var self = this;
      var modalSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };
      editor.addCommand('apester', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the video_embed widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};

          console.log(editor.widgets);
          if (editor.widgets.selected.length > 0 && editor.widgets.selected[0].name == 'apester') {
            existingValues = editor.widgets.selected[0].data.json;
          }
          Drupal.ckeditor.openDialog(editor, Drupal.url('ckeditor-apester/dialog/' + editor.config.drupal.format), existingValues, modalSaveWrapper, {
            title: Drupal.t('Apester Config'),
            dialogClass: 'apester-dialog'
          });
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      var widget = editor.document.createElement('p');
      var output =  '<interaction id="' + values.apester_embed_code + '"></interaction>';
      widget.setHtml(output);
      console.log(widget.getHtml());
      editor.insertHtml(widget.getOuterHtml());
    },

    /**
     * Register the widget.
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('apester', {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: true
      });
    },

    /**
     * Check if the element is an instance of the video widget.
     */
    upcast: function (element, data) {
      if (element.name != "interaction") {
        return;
      }
      data = element;
      element.setHtml(Drupal.theme('apesterEmbedWidget'));
      return element;
    },

    /**
     * Turns a transformed widget into the downcasted representation.
     */
    downcast: function (element) {
      element.setHtml(this.data);
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('apester', {
        label: Drupal.t('Apester'),
        command: 'apester',
        icon: this.path + 'images/icon.ico'
      });
    }
  });

  /**
   * The widget template viewable in the WYSIWYG after creating a video.
   */
  Drupal.theme.apesterEmbedWidget = function () {
    var output = [
      '<span class="apster-widget">',
        Drupal.t('[Apster Widget]'),
      '</span>'
    ].join('');

    return output;
  };

})(jQuery, Drupal, CKEDITOR);
