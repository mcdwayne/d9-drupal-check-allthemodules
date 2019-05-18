/**
 * @file
 * Drupal paragraph embed plugin.
 */

(function ($, Drupal, CKEDITOR) {
  "use strict";

  CKEDITOR.plugins.add('drupalparagraph', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    // The plugin initialization logic goes inside this method.
    beforeInit: function (editor) {
      // Configure CKEditor DTD for custom drupal-entity element.
      // @see https://www.drupal.org/node/2448449#comment-9717735
      var dtd = CKEDITOR.dtd, tagName;
      dtd['drupal-paragraph'] = {'#': 1};
      // Register drupal-entity element as allowed child, in each tag that can
      // contain a div element.
      for (tagName in dtd) {
        if (dtd[tagName].div) {
          dtd[tagName]['drupal-paragraph'] = 1;
        }
      }

      // Generic command for adding/editing entities of all types.
      editor.addCommand('editdrupalparagraph', {
        allowedContent: 'drupal-paragraph[!data-paragraph-id,!data-embed-button,data-entity-label]',
        requiredContent: 'drupal-paragraph[data-paragraph-id,data-embed-button]',
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          data = data || {};
          var existingElement = getSelectedEmbeddedParagraph(editor);

          var existingValues = {};
          if (existingElement && existingElement.$ && existingElement.$.firstChild) {
            var embedDOMElement = existingElement.$.firstChild;
            // Populate array with the embed item's current attributes.
            var attribute = null, attributeName;
            for (var key = 0; key < embedDOMElement.attributes.length; key++) {
              attribute = embedDOMElement.attributes.item(key);
              attributeName = attribute.nodeName.toLowerCase();
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              existingValues[attributeName] = existingElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }

          var embed_button_id = data.id ? data.id : existingValues['data-embed-button'];

          var dialogSettings = {
            dialogClass: 'paragraph-select-dialog',
            resizable: false,
            minWidth: 800
          };

          var paragraphSaveCallback = function (values) {
            var paragraphElement = editor.document.createElement('drupal-paragraph');
            var attributes = values.attributes
            for (var key in attributes) {
              paragraphElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(paragraphElement.getOuterHtml());
            if (existingElement) {
              // Detach the behaviors that were attached when the Paragraph content
              // was inserted.
              Drupal.runEmbedBehaviors('detach', existingElement.$);
              existingElement.remove();
            }
          };

          var url = Drupal.url('paragraph-embed/dialog/' + editor.config.drupal.format + '/' + embed_button_id);
          if ('data-paragraph-id' in existingValues) {
            url = url + '/' + existingValues['data-paragraph-id']
          }
          // Open the Paragraph embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, url, existingValues, paragraphSaveCallback, dialogSettings);
        }
      });

      // Register the Paragraph embed widget.
      editor.widgets.add('drupalparagraph', {
        allowedContent: 'drupal-paragraph[!data-paragraph-id,!data-embed-button,data-entity-label]',
        requiredContent: 'drupal-paragraph[data-paragraph-id,data-embed-button]',
        // Simply recognize the element as our own. The inner markup if fetched
        // and inserted the init() callback, since it requires the actual DOM
        // element.
        upcast: function (element) {
          var attributes = element.attributes;
          if (attributes['data-paragraph-id'] === undefined) {
            return;
          }
          // Generate an ID for the element, so that we can use the Ajax
          // framework.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered item.
        init: function () {
          /** @type {CKEDITOR.dom.element} */
          var element = this.element;
          // Use the Ajax framework to fetch the HTML, so that we can retrieve
          // out-of-band assets (JS, CSS...).
          var paragraphEmbedPreview = Drupal.ajax({
            base: element.getId(),
            element: element.$,
            url: Drupal.url('embed/preview/' + editor.config.drupal.format + '?' + $.param({
              value: element.getOuterHtml()
            })),
            progress: {type: 'none'},
            // Use a custom event to trigger the call.
            event: 'paragraph_embed_dummy_event'
          });
          paragraphEmbedPreview.execute();
        },

        // Downcast the element.
        downcast: function (element) {
          // Only keep the wrapping element.
          element.setHtml('');
          // Remove the auto-generated ID.
          delete element.attributes.id;
          return element;
        }
      });

      // Register the toolbar buttons.
      if (editor.ui.addButton) {
        for (var key in editor.config.DrupalParagraph_buttons) {
          var button = editor.config.DrupalParagraph_buttons[key];
          editor.ui.addButton(button.id, {
            label: button.label,
            data: button,
            allowedContent: 'drupal-paragraph[!data-paragraph-id,!data-embed-button]',
            click: function (editor) {
              editor.execCommand('editdrupalparagraph', this.data);
            },
            icon: button.image,
          });

        }
      }

      // Register context menu option for editing widget.
      if (editor.contextMenu) {
        for (var key in editor.config.DrupalParagraph_buttons) {
          var button = editor.config.DrupalParagraph_buttons[key];

          editor.addMenuGroup('drupalparagraph');

          editor.addMenuItem('drupalparagraph_' + button.id, {
            label: Drupal.t('Edit ') + button.label,
            icon: button.image,
            command: 'editdrupalparagraph',
            group: 'drupalparagraph'
          });
        }

        editor.contextMenu.addListener(function (element) {
          if (isEmbeddedParagraphWidget(editor, element)) {
            var button_id = element.getFirst().getAttribute('data-embed-button');
            var returnData = {};
            returnData['drupalparagraph_' + button_id] = CKEDITOR.TRISTATE_OFF;
            return returnData;
          }
        });
      }

      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {
        var element = getSelectedEmbeddedParagraph(editor) || evt.data.element;

        if (isEmbeddedParagraphWidget(editor, element)) {
          editor.execCommand('editdrupalparagraph');
        }
      });
    }
  });

  /**
   * Get the surrounding drupalparagraph widget element.
   *
   * @param {CKEDITOR.editor} editor
   */
  function getSelectedEmbeddedParagraph(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (isEmbeddedParagraphWidget(editor, selectedElement)) {
      return selectedElement;
    }

    return null;
  }

  /**
   * Returns whether or not the given element is a drupalparagraph widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEmbeddedParagraphWidget(editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    return widget && widget.name === 'drupalparagraph';
  }

  /**
   * Generates unique HTML IDs for the widgets.
   *
   * @returns {string}
   */
  function generateEmbedId() {
    if (typeof generateEmbedId.counter == 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'paragraph-embed-' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
