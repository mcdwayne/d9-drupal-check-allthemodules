/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($) {

  /**
   * A CKEditor plugin for video embeds.
   */
  CKEDITOR.plugins.add('flow_player', {

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
     *
     * @param {object} editor
     */
    init: function (editor) {
      this.registerWidget(editor);
      this.addCommand(editor);
      this.addIcon(editor);
    },

    /**
     * Add the command to the editor.
     *
     * @param editor
     */
    addCommand: function (editor) {
      var self = this;
      var modalSaveWrapper = function (values) {
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };
      editor.addCommand('flow_player', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the flow_player widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};
          if (editor.widgets.focused && editor.widgets.focused.name == 'flow_player') {
            existingValues = editor.widgets.focused.data.json;
          }

          var url = Drupal.url('flow-player-wysiwyg/dialog/' + editor.config.drupal.format);
          var data = {
            title: Drupal.t('Flow Player'),
            dialogClass: 'flow-player-dialog'
          };

          Drupal.ckeditor.openDialog(editor, url, existingValues, modalSaveWrapper, data);
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     *
     * @param editor
     * @param values
     */
    modalSave: function (editor, values) {
      // Insert a video widget that understands how to manage a JSON encoded
      // object, provided the flow_player property is set.
      var widget = editor.document.createElement('p');
      widget.setHtml(JSON.stringify(values));

      var sel = editor.getSelection();
      var range = sel.getRanges()[0];

      // no range, means the editor is empty. Select the range.
      if (!range) {
        range = editor.createRange();
        range.selectNodeContents(editor.editable());
      }

      sel.selectRanges([range]);

      editor.insertHtml(widget.getOuterHtml());
    },

    /**
     * Register the widget.
     *
     * @param editor
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('flow_player', {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: true
      });
    },

    /**
     * Check if the element is an instance of the video widget.
     *
     * @param element
     * @param data
     * @returns {*}
     */
    upcast: function (element, data) {
      // Upcast check must be sensitive to both HTML encoded and plain text.
      if (!element.getHtml().match(/^({(?=.*preview_thumbnail\b)(?=.*provider\b)(?=.*video_id\b)(?=.*player_id)(?=.*video)(.*)})$/)) {
        return;
      }
      data.json = JSON.parse(element.getHtml());
      element.setHtml(Drupal.theme('FlowPlayerWidget', data.json));
      return element;
    },

    /**
     * Turns a transformed widget into the downcasted representation.
     *
     * @param element
     */
    downcast: function (element) {
      element.setHtml(JSON.stringify(this.data.json));
    },

    /**
     * Add the icon to the toolbar.
     *
     * @param editor
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('flow_player', {
        label: Drupal.t('Flow Player'),
        command: 'flow_player',
        icon: this.path + '/icon.png'
      });
    }
  });

  /**
   * The widget template viewable in the WYSIWYG after creating a video.
   *
   * @param settings
   * @returns {string}
   * @constructor
   */
  Drupal.theme.FlowPlayerWidget = function (settings) {
    return [
      '<span class="flow-player-widget">',
      '<img class="flow-player-widget__image" src="' + Drupal.checkPlain(settings.preview_thumbnail) + '">',
      '<span class="flow-player-widget__summary">',
      Drupal.checkPlain(settings.video),
      '</span>',
      '</span>'
    ].join('');
  };

})(jQuery);
