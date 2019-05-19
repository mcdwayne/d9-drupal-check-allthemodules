/**
 * @file
 * Drupal Views embed plugin.
 */

(function ($, Drupal, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('drupalviews', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    // The plugin initialization logic goes inside this method.
    beforeInit: function (editor) {
      // Configure CKEditor DTD for custom drupal-views element.
      var dtd = CKEDITOR.dtd, tagName;
      dtd['drupal-views'] = {'#': 1};
      // Register drupal-views element as allowed child, in each tag that can
      // contain a div element.
      for (tagName in dtd) {
        if (dtd[tagName].div) {
          dtd[tagName]['drupal-views'] = 1;
        }
      }

      // Generic command for adding/editing views.
      editor.addCommand('editdrupalviews', {
        allowedContent: 'drupal-views[data-view-name,data-view-display,data-embed-button]',
        requiredContent: 'drupal-views[data-view-name,data-view-display]',
        modes: { wysiwyg : 1 },
        canUndo: true,
        exec: function (editor, data) {
          data = data || {};
          var existingElement = getSelectedEmbeddedViews(editor);

          var existingValues = {};
          if (existingElement && existingElement.$ && existingElement.$.firstChild) {
            var embedDOMElement = existingElement.$.firstChild;
            // Populate array with the views's current attributes.
            var attribute = null, attributeName;
            console.log(attribute);
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
            dialogClass: 'views-entity-embed-select-dialog',
            resizable: false
          };

          var saveCallback = function (values) {
            var viewElement = editor.document.createElement('drupal-views');
            var attributes = values.attributes;
            for (var key in attributes) {
              viewElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(viewElement.getOuterHtml());
            if (existingElement) {
              // Detach the behaviors that were attached when the views content
              // was inserted.
              Drupal.runEmbedBehaviors('detach', existingElement.$);
              existingElement.remove();
            }
          };
          // Open the views embed dialog for corresponding EmbedButton.
          Drupal.ckeditor.openDialog(editor, Drupal.url('views-entity-embed/dialog/' + editor.config.drupal.format + '/' + embed_button_id), existingValues, saveCallback, dialogSettings);
        }
      });

      // Register the views embed widget.
      editor.widgets.add('drupalviews', {
        // Minimum HTML which is required by this widget to work.
        allowedContent: 'drupal-views[data-view-name,data-view-display,data-views-arguments,data-embed-button]',
        requiredContent: 'drupal-views[data-view-name,data-view-display,data-views-arguments,data-embed-button]',

        // Simply recognize the element as our own. The inner markup if fetched
        // and inserted the init() callback, since it requires the actual DOM
        // element.
        upcast: function (element) {
          var attributes = element.attributes;
          if (attributes['data-view-name'] === undefined || (attributes['data-view-display'] === undefined)) {
            return;
          }

          // Generate an ID for the element, so that we can use the Ajax
          // framework.
          element.attributes.id = generateEmbedId();
          return element;
        },

        // Fetch the rendered views.
        init: function () {
          /** @type {CKEDITOR.dom.element} */
          var element = this.element;
          // Use the Ajax framework to fetch the HTML, so that we can retrieve
          // out-of-band assets (JS, CSS...).
          var viewEmbedPreview = Drupal.ajax({
            base: element.getId(),
            element: element.$,
            url: Drupal.url('embed/preview/' + editor.config.drupal.format + '?' + $.param({
              value: element.getOuterHtml()
            })),
            progress: {type: 'none'},
            // Use a custom event to trigger the call.
            event: 'views_entity_embed_dummy_event'
          });
          viewEmbedPreview.execute();
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
        for (var key in editor.config.DrupalViews_buttons) {
          var button = editor.config.DrupalViews_buttons[key];
          editor.ui.addButton(button.id, {
            label: button.label,
            data: button,
            allowedContent: 'drupal-views[!data-view-name,!data-view-display,!data-view-arguments, !data-embed-button]',
            click: function (editor) {
              editor.execCommand('editdrupalviews', this.data);
            },
            icon: button.image
          });
        }
      }

      // Register context menu option for editing widget.
      if (editor.contextMenu) {
        editor.addMenuGroup('drupalviews');
        editor.addMenuItem('drupalviews', {
          label: Drupal.t('Edit Views'),
          icon: this.path + 'views.png',
          command: 'editdrupalviews',
          group: 'drupalviews'
        });

        editor.contextMenu.addListener(function (element) {
          if (isEditableViewsWidget(editor, element)) {
            return { drupalviews: CKEDITOR.TRISTATE_OFF };
          }
        });
      }

      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {

        var element = getSelectedEmbeddedViews(editor) || evt.data.element;
        if (isEditableViewsWidget(editor, element)) {
          editor.execCommand('editdrupalviews');
        }
      });
    }
  });

  /**
   * Get the surrounding drupalviews widget element.
   *
   * @param {CKEDITOR.editor} editor
   */
  function getSelectedEmbeddedViews(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (isEditableViewsWidget(editor, selectedElement)) {
      return selectedElement;
    }

    return null;
  }

  /**
   * Checks if the given element is an editable drupalviews widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEditableViewsWidget(editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    if (!widget || widget.name !== 'drupalviews') {
      return false;
    }

    var button = $(element.$.firstChild).attr('data-embed-button');
    if (!button) {
      // If there was no data-embed-button attribute, not editable.
      return false;
    }

    // The button itself must be valid.
    return editor.config.DrupalViews_buttons.hasOwnProperty(button);
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
    return 'views-entity-embed' + generateEmbedId.counter++;
  }

})(jQuery, Drupal, CKEDITOR);
