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
      
      // save the plugin object for use in closures
      var myself = this;

      // Add the command for normal picker sidebar.
      var pickerCommand = editor.addCommand('wisski_entity_picker', {
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
        canUndo: false,
        exec: function () {
          this.toggleState();
        }
      });
      
      editor.on('instanceReady', function () {

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
          // typically to the right...
          position: {
            my: 'left center',
            at: 'right center',
            collision: 'flipfit none',
            of: $editorWrapper
          },
          // ... and make it the same height as the editor window
          height: $editorWrapper.height(),
          show: {
            effect: 'slide'
          },
          hide: {
            effect: 'slide'
          },
          pickCallback: function(data) {
            data.id = this._currentAnnoId;
            myself.saveCallback(data, editor);
          }
        };
console.log('height', $editorWrapper, $editorWrapper.height());

        myself.picker = Drupal.wisskiApus.entityPicker(pickerOptions);
        myself.picker.$element.on('dialog:close', function () {
          pickerCommand.setState(CKEDITOR.TRISTATE_OFF);
        });
        
        // create a delayed function that updates the picker content according
        // to the current selection
        var refresh = Drupal.debounce(function () {
            
          if (pickerCommand.state != CKEDITOR.TRISTATE_ON) {
            return;
          }
          if (!editor.getSelection()) {
            // not ready or not focused?
            return;
          }

          var range = editor.getSelection().getRanges()[0],
              text = '',
              annoIds,
              anno,
              i;

          myself.picker._currentAnnoId = null;

          // Get the text to search for based on selected text
          // or selected annotation 
          if (!range.collapsed) {
            text = editor.plugins.wisski_annotation.getRangeText(range);
          } else {
            // Get all Ids of annotations that overlap with the current
            // selection.
            // Currently we take the first one
            // TODO: better handling of multiple annotations
            var annoIds = editor.plugins.wisski_annotation.getIdsOfAnnotationsInRange(editor, range);
            if (annoIds.length != 0) {
              anno = Drupal.wisskiApus.parseAnnotation(annoIds[0], editor.document.$);
              if (anno.id) {
                // save id if we need to save the anno later 
                myself.picker._currentAnnoId = anno.id;
              }
              for (i in anno.$elements) {
                text += anno.$elements[i].text();
              }
            } else {
              // we currently dont allow setting an annotation on a collapsed
              // selection
            }
          }
          // tell the picker to update itself
          myself.picker.updateSearchTerm(text);
        }, 300, false);
        
        // we use a lot of triggers as we want to make sure that at least one
        // fires. unfortunately ckeditor does not provide an event that 
        // consistently fires whenever the cursor or selection changes
        editor.on('key', refresh);
        editor.on('doubleclick', refresh);
        editor.on('selectionChange', refresh);
        // set the listener now and on each time the editing mode
        // switches back to wysiwyg
        $(editor.elementPath().root.$).on('keypress click', refresh);
        editor.on('mode', function () {
          if (editor.mode == 'wysiwyg') {
            $(editor.elementPath().root.$).on('keypress click', refresh);
          }
        });
        
        // toggle picker visibility on state change
        pickerCommand.on('state', function (evt) {
          if (this.state == CKEDITOR.TRISTATE_ON && !myself.picker.open) {
            myself.picker.show();
            refresh();
          } else if (myself.picker.open) {
            myself.picker.close();
          }
        });
        pickerCommand.setState(CKEDITOR.TRISTATE_ON);
      
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

    },

    



      // Save callback 
    saveCallback: function(returnValues, editor) {

      var anno = {
        body : {},
        target: {}
      };
console.log(returnValues, 'rv');
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

      this.picker.showEntities(null);

    }
  
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
