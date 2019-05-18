/**
 * @file
 * The JavaScript file for the wysiwyg integration.
 */

(function ($, Drupal, CKEDITOR) {

  /**
   * A CKEditor plugin for advanced insert view.
   */
  CKEDITOR.plugins.add('insert_view_adv', {

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

    beforeInit: function (editor) {
      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {
        var element = editor.getSelection().getSelectedElement() || evt.data.element;
        if (isEditableInsertViewAdvWidget(editor, element)) {
          editor.execCommand('insert_view_adv');
        }
      });
    },

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
        editor.fire('saveSnapshot');
        self.modalSave(editor, values);
        editor.fire('saveSnapshot');
      };
      editor.addCommand('insert_view_adv', {
        exec: function (editor, data) {
          // If the selected element while we click the button is an instance
          // of the insert_view widget, extract it's values so they can be
          // sent to the server to prime the configuration form.
          var existingValues = {};
          if (editor.widgets.focused && editor.widgets.focused.name === 'insert_view_adv') {
            existingValues = editor.widgets.focused.data.json;
          }
          Drupal.ckeditor.openDialog(editor, Drupal.url('insert-view-wysiwyg/dialog/' + editor.config.drupal.format), existingValues, modalSaveWrapper, {
            title: Drupal.t('Advanced Insert View'),
            dialogClass: 'insert-view-dialog'
          });
        }
      });
    },

    /**
     * A callback that is triggered when the modal is saved.
     */
    modalSave: function (editor, values) {
      // Insert an advanced insert view widget that understands how to manage a JSON encoded
      // object, provided the view and arguments properties are set.
      var existingElement = editor.getSelection().getSelectedElement();
      if (existingElement) {
        Drupal.runInsertViewAdvBehaviors('detach', existingElement.$, []);
        existingElement.remove();
      }
      existingElement = editor.document.createElement('div');
      existingElement.setHtml(JSON.stringify(values));
      editor.insertHtml(existingElement.getOuterHtml());
    },

    /**
     * Register the widget.
     */
    registerWidget: function (editor) {
      var self = this;
      editor.widgets.add('insert_view_adv', {
        downcast: self.downcast,
        upcast: self.upcast,
        mask: true,
        init: function() {
          if (editor.config.enable_live_preview === true) {
            var element = this.element;
            $(element.$).find('.inserted-view-adv').each(function () {
              var advancedInsertView = Drupal.ajax({
                url: '/insert-view-adv/preview/'  + editor.config.drupal.format,
                base: element.getId(),
                element: element.$,
                submit: {
                  view_args: $(this).data('view-arguments'),
                  view_name: $(this).data('view-name'),
                  view_display_id: $(this).data('view-display'),
                  view_dom_id: $(this).data('dom-id'),
                },
                progress: {type: 'none'},
                // Use a custom event to trigger the call.
                event: 'insert_view_adv_dummy_event'
              });
              advancedInsertView.execute();
            });
          }
        }
      });
    },

    /**
     * Check if the element is an instance of the advanced insert view widget.
     */
    upcast: function (element, data) {
      // Upcast check must be sensitive to both HTML encoded and plain text.
      if (element.getHtml().indexOf('inserted_view_adv') === -1) {
        return;
      }
      data.json = JSON.parse(element.getHtml());
      element.setHtml(Drupal.theme('advancedInsertView', data.json));
      return element;
    },

    /**
     * Turns a transformed widget into the downcasted representation.
     */
    downcast: function (element) {
      element.setHtml(JSON.stringify(this.data.json));
    },

    /**
     * Add the icon to the toolbar.
     */
    addIcon: function (editor) {
      if (!editor.ui.addButton) {
        return;
      }
      editor.ui.addButton('insert_view_adv', {
        label: Drupal.t('Advanced Insert View'),
        command: 'insert_view_adv',
        icon: this.path + '/icon.png'
      });
    }
  });

  /**
   * Checks if the given element is an editable drupalentity widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEditableInsertViewAdvWidget(editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    if (widget || widget.name === 'insert_view_adv') {
      return true;
    }

    return false;
  }

  /**
   * The widget template viewable in the WYSIWYG after inserting the view.
   */
  Drupal.theme.advancedInsertView = function (settings) {
    var view_arguments = '';
    var view_arguments_token = '';
    if (settings.arguments !== null && settings.arguments.length > 0) {
      for (var i = 0; i < settings.arguments.length; i++) {
        view_arguments = view_arguments + settings.arguments[i];
        if (i !== (settings.arguments.length - 1)) {
          view_arguments = view_arguments + '/';
        }
      }
      view_arguments_token = '=' + view_arguments;
    }

    var view_details = settings.inserted_view_adv.split('=');
    var output = '[view:' + settings.inserted_view_adv + '' + view_arguments_token + ']';
    var dom_id = settings.inserted_view_adv.replace('=', '-') + view_arguments.replace('/', '-');

    return [
      '<span class="inserted-view-adv js-view-dom-id-'+ dom_id + '" data-dom-id="' + dom_id + '" data-view-name="' + view_details[0] +'" data-view-display="' + view_details[1] + '" data-view-arguments="' + view_arguments + '">',
      output,
      '</span>'
    ].join('');
  };

})(jQuery, Drupal, CKEDITOR);
