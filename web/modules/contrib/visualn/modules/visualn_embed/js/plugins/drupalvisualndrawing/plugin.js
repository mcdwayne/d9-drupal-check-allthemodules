(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('drupalvisualndrawing', {
    // This plugin requires the Widgets System defined in the 'widget' plugin.
    requires: 'widget',

    icons: 'imagepopup',
    hidpi: true,
    beforeInit: function (editor) {
      // Configure CKEditor DTD for custom drupal-entity element.
      // @see https://www.drupal.org/node/2448449#comment-9717735
      var dtd = CKEDITOR.dtd, tagName;
      dtd['drupal-visualn-drawing'] = {'#': 1};
      // Register drupal-visualn-drawing element as allowed child, in each tag that can
      // contain a div element.
      for (tagName in dtd) {
        if (dtd[tagName].div) {
          dtd[tagName]['drupal-visualn-drawing'] = 1;
        }
      }

      // Implementation before initializing plugin.
      editor.addCommand( 'editdrawing', {
        // @todo: is 'align' and other properties required here?
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        //allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id,data-visualn-drawing-align]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        modes: { wysiwyg : 1 },
        canUndo: true,
        exec: function (editor, data) {

          var myPluginSaveCallback = function(data) {
            //console.log(data.drawing_id);

            var entityElement = editor.document.createElement('drupal-visualn-drawing');

            var attributes = data.tag_attributes;
            for (var key in attributes) {
              entityElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(entityElement.getOuterHtml());
          };

          var drawing_id = getSelectionDrawingId(editor);
          // @todo: use existingElement if keeping drawing properties after replacement is neeeded
          //   though iframe properties should be reset/updated then since iframe entry
          //   would point to a different drawing (reset hash or keep hash and update entry)
          //var existingElement = getSelectedEmbeddedDrawing(editor);

          // Populate existing values of form.
          var existingValues = {};
          if (drawing_id) {
            existingValues['data-visualn-drawing-id'] = drawing_id;
          }
          var dialogSettings = {
            dialogClass: 'ui-dialog-visualn'
          };
          // Open dialog form.
          Drupal.ckeditor.openDialog(editor,
            Drupal.url('visualn_embed/form/drawing_embed_dialog/' + editor.config.drupal.format),
            existingValues,
            myPluginSaveCallback,
            dialogSettings
          );
        }
      });

      // @todo: rework all these methods and the file code itself completely
      editor.addCommand( 'editvisualndrawing', {
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        modes: { wysiwyg : 1 },
        canUndo: false,
        //canUndo: true,
        exec: function (editor, data) {

          // @todo: this is a copy-paste form editdrawing method
          var myPluginSaveCallback = function(data) {

            var entityElement = editor.document.createElement('drupal-visualn-drawing');

            var attributes = data.tag_attributes;
            for (var key in attributes) {
              entityElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(entityElement.getOuterHtml());
          };

          var drawing_id = getSelectionDrawingId(editor);


          // @todo: check drawing_id


          //var drawing_id = existingElement.data('visualn-drawing-id');
          //var drawing_id = attribute.nodeValue;

          // @todo: add callback that would change ckeditor drawing markup (e.g. at title update)

          // @todo: the route callback should check if drawings exists

          // @todo: callback paths could be passed as settings from server-side (or should be tracked for changes)

          var existingValues = {};

          // @todo: also see https://www.drupal.org/project/drupal/issues/1918744
          var dialogSettings = {
            // @todo: this is getting overridden in ckeditor.js
            //   see Drupal.ckeditor.openDialog()
            //width: '800'
            dialogClass: 'ui-dialog-visualn'
          };

          // @todo: check permissions if user has access to edit the drawing (same for preview and other actions)
          //   also do not show inactive menu items for 'Drawing not found' case

          // Open dialog form.
          // @todo: how to pass dialog id so that it could be close on save by ajax commands response?
          Drupal.ckeditor.openDialog(editor,
            //Drupal.url('visualn_embed/form/drawing_embed_dialog/' + editor.config.drupal.format),
            Drupal.url('visualn-ckeditor/drawing/' + drawing_id + '/edit_content'),

            //Drupal.url('visualn-ckeditor/drawing/' + drawing_id + '/edit'),
            //Drupal.url('my_module/dialog/image/' + editor.config.drupal.format),
            existingValues,
            myPluginSaveCallback,
            dialogSettings
          );



          return;
        }
      });

      editor.addCommand( 'previewvisualndrawing', {
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        modes: { wysiwyg : 1 },
        canUndo: false,
        //canUndo: true,
        exec: function (editor, data) {


          var myPluginSaveCallback = function(data) {
            //console.log(data.drawing_id);

            // @todo: what if user clicked 'edit' on the preview and edited the drawing
            //console.log('preview content');
          };

          var drawing_id = getSelectionDrawingId(editor);

          // Populate existing values of form.
          var existingValues = {};
          var dialogSettings = {
            dialogClass: 'ui-dialog-visualn'
          };
          // Open dialog form.
          Drupal.ckeditor.openDialog(editor,
            Drupal.url('visualn-drawing-embed/real-preview-content/' + drawing_id),
            existingValues,
            myPluginSaveCallback,
            dialogSettings
          );

        }
      });

      editor.addCommand( 'replacevisualndrawing', {
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        modes: { wysiwyg : 1 },
        canUndo: false,
        //canUndo: true,
        exec: function (editor, data) {

          // @todo: consider an option to keep drawing properties for replacing drawing

          // @todo: doesn't undo

          // @todo: this is mostly a copy-paste form insert command (currently called editvisualndrawing)
          // @todo: rename editvisualndrawing to insertvisualndrawing though it can be used to edit
          //   when a drawing is selected and button clicked (also consider the case of not found drawings)
          var myPluginSaveCallback = function(data) {
            //console.log(data.drawing_id);

            var entityElement = editor.document.createElement('drupal-visualn-drawing');

            var attributes = data.tag_attributes;
            for (var key in attributes) {
              entityElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(entityElement.getOuterHtml());
          };

          // Populate existing values of form.
          var existingValues = {};
          var drawing_id = getSelectionDrawingId(editor);
          if (drawing_id) {
            existingValues['data-visualn-drawing-id'] = drawing_id;
          }
          var dialogSettings = {
            dialogClass: 'ui-dialog-visualn'
          };
          // Open dialog form.
          Drupal.ckeditor.openDialog(editor,
            Drupal.url('visualn_embed/form/drawing_embed_dialog/' + editor.config.drupal.format),
            existingValues,
            myPluginSaveCallback,
            dialogSettings
          );
        }
      });

      editor.addCommand( 'configurevisualndrawing', {
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        modes: { wysiwyg : 1 },
        canUndo: false,
        //canUndo: true,
        exec: function (editor, data) {
          // @todo: this is a copy-paste form editdrawing method
          var myPluginSaveCallback = function(data) {
            //console.log(data.drawing_id);

            // @todo: replace widget markup and element tag itself using values
            //   from the properties dialog

            //console.log('drawing properties');
            var entityElement = editor.document.createElement('drupal-visualn-drawing');

            var attributes = data.tag_attributes;
            for (var key in attributes) {
              entityElement.setAttribute(key, attributes[key]);
            }
            editor.insertHtml(entityElement.getOuterHtml());
          };

          /*
          var drawing_id = getSelectionDrawingId(editor);

          // Populate existing values of form.
          var existingValues = {
            drawing_id: drawing_id
          };
          */

          var existingElement = getSelectedEmbeddedDrawing(editor);



          // @todo: move into a method since it is reused also in other places
          // Populate existing values of form.
          var existingValues = {};
          if (existingElement && existingElement.$ && existingElement.$.firstChild) {
            var embedDOMElement = existingElement.$.firstChild;
            // Populate array with the entity's current attributes.
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

          //var embed_button_id = data.id ? data.id : existingValues['data-embed-button'];


          //console.log(existingValues);


          var dialogSettings = {
            dialogClass: 'ui-dialog-visualn'
          };
          // Open dialog form.
          Drupal.ckeditor.openDialog(editor,
            Drupal.url('visualn_embed/form/drawing_properties_dialog/' + editor.config.drupal.format),
            existingValues,
            myPluginSaveCallback,
            dialogSettings
          );
        }
      });

      editor.widgets.add('drupalvisualndrawing', {
        allowedContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        requiredContent: 'drupal-visualn-drawing[data-visualn-drawing-id]',
        upcast: function (element) {
          var attributes = element.attributes;

          if (attributes['data-visualn-drawing-id'] === undefined) {
            //if (attributes['data-entity-type'] === undefined || (attributes['data-entity-id'] === undefined && attributes['data-entity-uuid'] === undefined) || (attributes['data-view-mode'] === undefined && attributes['data-entity-embed-display'] === undefined)) {
            return;
          }


          element.attributes.id = generateEmbedId();

          return element;
        },
        init: function() {
          var element = this.element;

          var attributes = element.$.attributes;
          // @todo: add check if not empty
          var drawing_id = attributes['data-visualn-drawing-id'].value;
          //var drawing_id = attributes['data-visualn-drawing-id'];
          //console.log(drawing_id);

          // set keys to generate ckeditor widget placeholder for the drawing
          // @todo: rework widget placeholder template
          var params = {};
          var param_keys = {
            align: 'data-align',
            width: 'width',
            height: 'height',
            settings: 'data-visualn-drawing-settings'
          };
          $.each(param_keys, function(key, attr){
            if (typeof attributes[attr] != 'undefined') {
              params[key] = attributes[attr].value;
            }
          });

          var entityEmbedPreview = Drupal.ajax({
            base: element.getId(),
            element: element.$,

            // @todo: use 'widget' or 'placeholder' in the url instead of 'preview'
            url: Drupal.url('visualn-drawing-embed/preview/' + editor.config.drupal.format + '/' + drawing_id + '?' + $.param(params)),
            /*
            url: Drupal.url('embed/preview/' + editor.config.drupal.format + '?' + $.param({
              value: element.getOuterHtml()
            })),
            */
            progress: {type: 'none'},
            // Use a custom event to trigger the call.
            event: 'entity_embed_dummy_event'
          });


          entityEmbedPreview.execute();

          //console.log(element.getId());

          // @todo: see https://docs.ckeditor.com/ckeditor4/latest/guide/widget_sdk_tutorial_2.html
          /*
          if ( this.element.hasClass( 'align-left' ) )
            this.setData( 'align', 'left' );
          if ( this.element.hasClass( 'align-right' ) )
            this.setData( 'align', 'right' );
          if ( this.element.hasClass( 'align-center' ) )
            this.setData( 'align', 'center' );
            */
        },
        data: function() {
          // @todo: see https://docs.ckeditor.com/ckeditor4/latest/guide/widget_sdk_tutorial_2.html
          if ( this.data.align )
            this.element.addClass( 'align-' + this.data.align );

          if (typeof this.element.$.attributes['data-align'] != 'undefined') {
            var align = this.element.$.attributes['data-align'].value;
            // @todo: exclude "none" value
            this.element.addClass( 'align-' + align );
          }

          // @todo: enable manual resizing

        }
      });

      editor.ui.addButton('Visualn-drawing-ckeditor-button', {
        label: Drupal.t('VisualN Drawing'),
        // Note that we use the original image2 command!
        command: 'editdrawing',
        icon: this.path + 'images/icon.png'
      });

      // Register context menu option for editing widget.
      if (editor.contextMenu) {
        editor.addMenuGroup('drupalvisualndrawing');
        editor.addMenuItem('editvisualndrawing', {
          label: Drupal.t('Edit VisualN Drawing'),
          icon: this.path + 'images/icon.png',
          command: 'editvisualndrawing',
          group: 'drupalvisualndrawing'
        });

        editor.addMenuItem('previewvisualndrawing', {
          label: Drupal.t('Open Drawing Preview'),
          // @todo: set icon image path
          //icon: this.path + 'images/icon.png',
          command: 'previewvisualndrawing',
          group: 'drupalvisualndrawing'
        });

        editor.addMenuItem('replacevisualndrawing', {
          label: Drupal.t('Choose other...'),
          // @todo: set icon image path
          //icon: this.path + 'images/icon.png',
          command: 'replacevisualndrawing',
          group: 'drupalvisualndrawing'
        });

        editor.addMenuItem('configurevisualndrawing', {
          label: Drupal.t('Drawing Properties'),
          // @todo: set icon image path
          //icon: this.path + 'images/icon.png',
          command: 'configurevisualndrawing',
          group: 'drupalvisualndrawing'
        });

        // @todo:
        editor.contextMenu.addListener(function(element) {
          if (isEditableDrawingWidget(editor, element)) {
            return { 'editvisualndrawing' : CKEDITOR.TRISTATE_OFF, 'previewvisualndrawing' : CKEDITOR.TRISTATE_OFF, 'replacevisualndrawing' : CKEDITOR.TRISTATE_OFF, 'configurevisualndrawing' : CKEDITOR.TRISTATE_OFF };
          }
        });
      }

      // Execute widget editing action on double click.
      editor.on('doubleclick', function (evt) {
        editor.execCommand('configurevisualndrawing');
        // @todo: open 'edit' dialog
        /*
        var element = getSelectedEmbeddedEntity(editor) || evt.data.element;

        if (isEditableEntityWidget(editor, element)) {
          editor.execCommand('editdrawing');
        }
        */
      });

      /*
      insertContent = function(html) {
        editor.insertHtml(html);
      }
      */
    },
    init: function (editor) {
    },
  });


  function generateEmbedId() {
    if (typeof generateEmbedId.counter == 'undefined') {
      generateEmbedId.counter = 0;
    }
    return 'visualn-drawing-embed-' + generateEmbedId.counter++;
  }

  function getSelectionDrawingId(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    var existingElement = selectedElement;

    //console.log(selectedElement);

    var drawing_id = null;

    if (existingElement && existingElement.$ && existingElement.$.firstChild) {
      var embedDOMElement = existingElement.$.firstChild;
      // Populate array with the entity's current attributes.
      var attribute = null, attributeName;
      for (var key = 0; key < embedDOMElement.attributes.length; key++) {
        attribute = embedDOMElement.attributes.item(key);
        attributeName = attribute.nodeName.toLowerCase();
        if (attributeName === 'data-visualn-drawing-id') {
          //var drawing_id = existingElement.data('visualn-drawing-id');
          var drawing_id = attribute.nodeValue;
          //console.log(drawing_id);
        }
        /*
           if (attributeName.substring(0, 15) === 'data-cke-saved-') {
           continue;
           }
           existingValues[attributeName] = existingElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
           */
      }
    }

    return drawing_id;
  }

  /**
   * Get the surrounding drupalentity widget element.
   *
   * @param {CKEDITOR.editor} editor
   */
  function getSelectedEmbeddedDrawing(editor) {
    //function getSelectedEmbeddedEntity(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    return selectedElement;
    /*
    if (isEditableDrawingWidget(editor, selectedElement)) {
      return selectedElement;
    }

    return null;
    */
  }

  /**
   * Checks if the given element is an editable drupalentity widget.
   *
   * @param {CKEDITOR.editor} editor
   * @param {CKEDITOR.htmlParser.element} element
   */
  function isEditableDrawingWidget (editor, element) {
    var widget = editor.widgets.getByElement(element, true);
    if (!widget || widget.name !== 'drupalvisualndrawing') {
      return false;
    }

    return true;

    /*
    var button = $(element.$.firstChild).attr('data-embed-button');
    if (!button) {
      // If there was no data-embed-button attribute, not editable.
      return false;
    }

// The button itself must be valid.
    return editor.config.DrupalEntity_buttons.hasOwnProperty(button);
    */
}

})(jQuery, Drupal, drupalSettings, CKEDITOR);
