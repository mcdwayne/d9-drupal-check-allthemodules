
/**
 * @file
 * EntityLinkerDialog plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  "use strict";

  CKEDITOR.plugins.add('wisski_annotation_dialog', {
    init: function (editor) {
      
      // save the plugin object for use in closures
      var myself = this;

      // Add the command for toggling dialog sidebar.
      myself.dialogToggleCommand = editor.addCommand('toggleWisskiAnnotationDialog', {
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
        exec: function () {
          this.toggleState();
        }
      });
      
      // Add the command for searching entities in the sidebar.
      editor.addCommand('wisskiSearchEntities', {
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
          var sel = editor.getSelection();
          var range = !sel ? null : sel.getRanges()[0];
          var text = (!range || range.collapsed) ? '' : editor.plugins.wisski_annotation.getRangeText(range);
          var search;
          // if there is nothing to search we don't allow
          // searching entities
          if (text == '') {
            myself.dialog.clear();
            return;
          }
          // prepare search structure
          search = {
            data: text
            // this is the default settings, no need here
            // pipe: drupalSettings.wisskiApus.entityPicker.pipe
          }
          // show dialog and display search entity form
          myself.dialogToggleCommand.setState(CKEDITOR.TRISTATE_ON);
          myself.dialog.searchEntities(search, function (d) {
            myself.saveCallback(d, editor);
          });
        }
      });
      
      editor.on('instanceReady', function () {

        var $editorWrapper = $(editor.element.$).parent();
        var dialogOptions = {
          id: 'wisski-annotation-dialog--' + editor.id,
          target: $editorWrapper,
          closeOnEscape: false,
          dialogClass: 'wisski-ckeditor-annotation-dialog',
          // autoResize is handled by Drupal's dialog.position.js and repositions
          // the dialog to the middle of the screen. We don't want that ...
          autoResize: false,
          // ... instead we want to tie the dialog to the editor wrapper element,
          // typically to the right...
          position: {
            my: 'left center',
            at: 'right center',
            collision: 'flipfit none',
            of: $editorWrapper
          },
          // ... and make it the same height as the editor window
          height: $editorWrapper.parent().parent().height(),
          show: {
            effect: 'slide'
          },
          hide: {
            effect: 'slide'
          },
          entityPicker: {
            pickCallback: function(data) {
              data.id = this._currentAnnoId;
              myself.saveCallback(data, editor);
            }
          }
        };

        myself.dialog = Drupal.wisskiApus.dialog(dialogOptions);
        myself.dialog.$element.on('dialog:close', function () {
          dialogToggleCommand.setState(CKEDITOR.TRISTATE_OFF);
        });
        
 

        var refreshAnnotation = function(evt) {
          var sel = editor.getSelection();
          var range = !sel ? null : sel.getRanges()[0];
          var annos = !range ? [] : editor.plugins.wisski_annotation.getIdsOfAnnotationsInRange(editor, range);
          // either show an anno or clear dialog
          if (annos.length == 1) {
            // TODO: what if there are multiple selected annotations?
            var anno = Drupal.wisskiApus.parseAnnotation(annos[0], editor.document.$);
            myself.dialog.showAnnotation(anno, editor.document.$);
          } else {
            myself.dialog.clear();
          }

        }
        
        // update the dialog to show the currently selected annotation
        editor.on('selectionChange', refreshAnnotation);
        // doubleclick starts search for selected string 
        editor.on('doubleclick', function (evt) {
          evt.editor.execCommand('wisskiSearchEntities');
        });
        
        // toggle dialog visibility on state change
        myself.dialogToggleCommand.on('state', function (evt) {
          if (this.state == CKEDITOR.TRISTATE_ON && !myself.dialog.open) {
            myself.dialog.show();
            refreshAnnotation();
          } else if (myself.dialog.open) {
            myself.dialog.close();
          }
        });
        myself.dialogToggleCommand.setState(CKEDITOR.TRISTATE_ON);
      
      });


      // CTRL + WS.
      editor.setKeystroke(CKEDITOR.CTRL + 32, 'wisskiSearchEntities');

      // Add buttons.
      if (editor.ui.addButton) {
        editor.ui.addButton('ToggleWisskiAnnotationDialog', {
          label: Drupal.t('Show/Hide annotation sidebar'),
          command: 'toggleWisskiAnnotationDialog',
          icon: this.path + '/annotationDialog.png'
        });
      }

    },


    /** Callback when an entity was picked in the picker dialog
    */
    saveCallback: function(returnValues, editor) {

      var anno = {
        body : {},
        target: {}
      };
      if (!!returnValues.id) {
        // Use the anno ID to identify the anno 
        anno.body.id = returnValues.id;
      } else {
        // Create a new link element if needed.
        var selection = editor.getSelection();
        var range = selection.getRanges(1)[0];
        if (range.collapsed) {
          // atm we do not handle settings annotation on collapsed ranges
          return;
        }
        anno.body.domRange = range;
      }

      anno.target.ref = returnValues.uri;

      editor.execCommand('wisskiSaveAnnotation', anno);

      editor.plugins.wisski_annotation_dialog.dialog.entityPicker.showEntities(null);

    }
  
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
