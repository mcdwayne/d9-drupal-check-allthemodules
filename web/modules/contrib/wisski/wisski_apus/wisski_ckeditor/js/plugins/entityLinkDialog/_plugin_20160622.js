/**
 * @file
 * EntityLinkerDialog plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('wisski_quick_entity_picker', {
    init: function (editor) {
      
      var myself = this;

      // Add the commands for quick picker.
      editor.addCommand('wisski_quick_entity_picker', {
        allowedContent: new CKEDITOR.style({
          element: 'span',
          attributes: {
            'about': '',  // RDFa
            'type': '',   // RDFa
            'id': '', // ID of the annotation
            'data-wisski-anno': ''
          }
        }),
        requiredContent: new CKEDITOR.style({
          element: 'span',
          attributes: {
            'id': ''
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var linkElement = getSelectedLink(editor);
          var linkDOMElement = null;

console.log("heureka picker");
          // Set existing values based on selected element.
          var existingValues = {};
          if (linkElement && linkElement.$) {
            linkDOMElement = linkElement.$;

            // Populate an array with the link's current attributes.
            var attribute = null;
            var attributeName;
            for (var attrIndex = 0; attrIndex < linkDOMElement.attributes.length; attrIndex++) {
              attribute = linkDOMElement.attributes.item(attrIndex);
              attributeName = attribute.nodeName.toLowerCase();
              // Don't consider data-cke-saved- attributes; they're just there
              // to work around browser quirks.
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              // Store the value for this attribute, unless there's a
              // data-cke-saved- alternative for it, which will contain the
              // quirk-free, original value.
              existingValues[attributeName] = linkElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }


          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: '',
            dialogClass: 'editor-wisski-quickEntityPicker-dialog'
          };

// BEGIN: emulate Drupal.ckeditor.openDialog to get a non-modal dialog

          // Locate a suitable place to display our loading indicator.
          var $target = $(editor.container.$);
          if (editor.elementMode === CKEDITOR.ELEMENT_MODE_REPLACE) {
            $target = $target.find('.cke_contents');
          }
          
          // Remove any previous loading indicator.
          $target.css('position', 'relative').find('.ckeditor-dialog-loading').remove();
          
          // Add a consistent dialog class.
          var classes = dialogSettings.dialogClass ? dialogSettings.dialogClass.split(' ') : [];
          classes.push('ui-dialog--narrow');
          dialogSettings.dialogClass = classes.join(' ');
          dialogSettings.autoResize = window.matchMedia('(min-width: 600px)').matches;
          dialogSettings.width = 'auto';
          
          var url = Drupal.url('wisski/ckeditor/dialog/quickEntityPicker/' + editor.config.drupal.format + '?q=' + encodeURIComponent(''));
          
          var ckeditorAjaxDialog = Drupal.ajax({
            dialog: dialogSettings,
            dialogType: 'dialog',
            selector: '.ckeditor-dialog-loading-link',
            url: url,
            progress: {type: 'throbber'},
            submit: {
              editor_object: existingValues
            }
          });
          ckeditorAjaxDialog.execute();
          
          // kidnap the editor's callback functionality
          // TODO: use other callback function
          Drupal.ckeditor.saveCallback = function(returnValues) {
            myself.saveCallback(returnValues, editor);
          };
//END emulate
        }

      }); // end command wisski_quick_entity_picker


      // Add the command for normal picker dialog.
      editor.addCommand('wisski_entity_picker', {
        allowedContent: new CKEDITOR.style({
          element: 'span',
          attributes: {
            'about': '',  // RDFa
            'type': '',   // RDFa
            'id': '', // ID of the annotation
            'data-wisski-anno': '',
            'data-wisski-anno-id': '',
            'data-wisski-cat': '',
            'data-wisski-target': ''
          }
        }),
        requiredContent: new CKEDITOR.style({
          attributes: {
            'data-wisski-anno-id': '',
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          
          var range = editor.getSelection().getRanges()[0];

          // Get all Ids of annotations that overlap with the current
          // selection.
          // Currently we take the first one
          // TODO: better handling of multiple annotations
          var annoIds = editor.plugins.wisski_annotation.getIdsOfAnnotationsInRange(editor, range);

          // Set existing values based on selected element.
          var anno = {};

          if (annoIds.length != 0) {
            anno = Drupal.wisskiApus.parseAnnotation(annoIds[0], editor.document.$);
            // we have to delete the jquery $elements
            // as they are not serializable
            if (!!anno.body && !!anno.body.$elements) delete(anno.body.$elements);
          } else if (range.collapsed) {
          // check if there is selected text
          // we currently dont allow setting an annotation on a collapsed
          // selection
            return;
          }


          
          var text = editor.plugins.wisski_annotation.getRangeText(range);

          var values = {
            id : !anno.id ? '' : anno.id,
            selection: text,
            anno: anno,
            href: !anno.target.ref ? text : anno.target.ref
          }

          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: '',
            dialogClass: 'editor-wisski-entity-picker-dialog'
          };

          var saveCallback = function(returnValues) {
            myself.saveCallback(returnValues, editor, values.id);
          }

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(
            editor,
            Drupal.url('linkit/dialog/linkit/' + editor.config.drupal.format), 
//            Drupal.url('wisski/ckeditor/dialog/quickEntityPicker/' + editor.config.drupal.format), 
            values, 
            saveCallback, 
            dialogSettings
          );
        }
      });

      // CTRL + WS.
      editor.setKeystroke(CKEDITOR.CTRL + 32, 'wisski_quick_entity_picker');

      // Add buttons.
      if (editor.ui.addButton) {
        editor.ui.addButton('EntityPicker', {
          label: Drupal.t('Annotate With Entity'),
          command: 'wisski_entity_picker',
          icon: this.path + '/entityLinkDialog.png'
        });
      }
      
      var $editorWrapper = $(editor.element.$).parent();
      var pickerOptions = {
        id: 'wisski-entity-picker-' + editor.id,
        target: $editorWrapper,
        closeOnEscape: false,
        dialogClass: 'wisski-ckeditor-sidebar-entity-picker',
        // autoResize is handled by Drupal's dialog.position.js and repositions
        // the dialog to the middle of the screen. We don't want that ...
        autoResize: false,
        // ... instead we want to tie the dialog to the editor wrapper element,
        // typically to the right.
        position: {
          my: 'left center',
          at: 'right center',
          collision: 'flipfit none',
          of: $editorWrapper
        },
        height: $editorWrapper.height()
      }
      var picker = Drupal.wisskiApus.entityPicker(pickerOptions);
      picker.show();
      picker.updateSearchTerm("Test");
console.log(picker);

/*
      editor.on('doubleclick', function (evt) {
        var element = getSelectedLink(editor) || evt.data.element;

        if (!element.isReadOnly()) {
          if (element.is('a')) {
            editor.getSelection().selectElement(element);
            editor.getCommand('entityLinkerDialog').exec();
          }
        }
      });

      // If the "menu" plugin is loaded, register the menu items.
      if (editor.addMenuItems) {
        editor.addMenuItems({
          entityLinkerDialog: {
            label: Drupal.t('Edit Link'),
            command: 'entityLinkerDialog',
            group: 'link',
            order: 1
          }
        });
      }

      // If the "contextmenu" plugin is loaded, register the listeners.
      if (editor.contextMenu) {
        editor.contextMenu.addListener(function (element, selection) {
          if (!element || element.isReadOnly()) {
            return null;
          }
          var anchor = getSelectedLink(editor);
          if (!anchor) {
            return null;
          }

          var menu = {};
          if (anchor.getAttribute('href') && anchor.getChildCount()) {
            menu = {
              entityLinkerDialog: CKEDITOR.TRISTATE_OFF
            };
          }
          return menu;
        });
      }
      */
    },

      // Save callback 
    saveCallback: function(returnValues, editor, id) {

      var anno = {
        body : {},
        target: {}
      };
console.log(returnValues, 'rv');
      if (!!id) {
        // Use the anno ID to identify the anno 
        anno.body.id = returnValues.id;
      } else {
        // Create a new link element if needed.
        var selection = editor.getSelection();
        var range = selection.getRanges(1)[0];

        if (range.collapsed) {
          // this should not happen as we don't call the dialog if selection 
          // is empty
          throw "Cannot set annotation on empty selection";

//          editor.plugins.wisski_annotation.selectFullWords(editor, range);
          
        }

        anno.body.domRange = range;

        // Create the new link by applying a style to the new text.
/*        var style = new CKEDITOR.style({element: 'span', attributes: returnValues.attributes});
        style.type = CKEDITOR.STYLE_INLINE;
        style.applyToRange(range);
        range.select();*/
      }

      anno.target.ref = returnValues.attributes.href;

      editor.execCommand('wisskiSaveAnnotation', anno);

    }
  
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
