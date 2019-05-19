/**
* @author Martin Scholz, WissKI: wiss-ki.eu
*/


CKEDITOR.plugins.add( 'wisski_apus_adapter', {

  icons: 'togglesubject',
  
  onLoad: function() {

    /* -- Global settings -- */

    CKEDITOR.WissKI = CKEDITOR.WissKI || {};
    CKEDITOR.WissKI.core = CKEDITOR.WissKI.core || {};

    CKEDITOR.WissKI.core.jq = CKEDITOR.WissKI.core.jq || jQuery;
    

    /* -- Global functions -- */
    
    CKEDITOR.WissKI.core.calcSelections = function ( editor ) {

      var sel = editor.getSelection();
      sel.lock();
      
      var old_textranges = editor.WissKI.core.textranges;
      var old_touched_annos = editor.WissKI.core.touched_annotations;
      var old_is_sel_collapsed = editor.WissKI.core.is_sel_collapsed;
      
      if (sel.getType() != CKEDITOR.SELECTION_TEXT) {
      
        editor.WissKI.core.textranges = [];
        editor.WissKI.core.touched_annotations = {};
        editor.WissKI.core.is_sel_collapsed = true;

      } else {
        
        var jq = CKEDITOR.WissKI.core.jq;
        // get all annotations overlapping with the cursor / the current selection
        var annos = {};
        var rngs = sel.getRanges();
        for (var i in rngs) {
          var range = rngs[i];
          editor.WissKI.core.textranges.push(range);
          var aa = CKEDITOR.WissKI.core.getAnnosOverlappingWithRange(editor, range);
          for (var j in aa) {
            if (!annos[j]) {
              annos[j] = j;
            }
          }
        }

        // no text selected
        editor.WissKI.core.is_sel_collapsed = sel.getSelectedText() == '';

        sel.unlock();

      }
      
      var change = old_is_sel_collapsed != editor.WissKI.core.is_sel_collapsed;
        
      if (old_textranges.length != editor.WissKI.core.textranges.length) {
        change = true;
//      } else if () {

      }

      if (change) {
        editor.fire('wisski.selAnnoChange', editor, old_textranges, old_touched_annos, old_is_sel_collapsed);
      }
      
    };

    /**Convert a CKEditor Range object to start and end offset in pure text
    */
    CKEDITOR.WissKI.core.rangeToTextRange = function( editor, range ) {
      
      // helper range for range start
      var rng = editor.createRange();
      rng.setStart(editor.editable(), 0);
      rng.setEnd(range.startContainer, range.startOffset);
      var rngtext = CKEDITOR.WissKI.core.rangeToString(rng);
      var s = rngtext.length;
      rngtext = CKEDITOR.WissKI.core.rangeToString(range);
      var e = s + rngtext.length;
      return [s, e];

    };
 
    /**Convert a W3C Range object to start and end offset in pure text
    */
    CKEDITOR.WissKI.core.getAnnosOverlappingWithRange = function( editor, range ) {
      
      var model = editor.WissKI.model;
      
      var w = CKEDITOR.dom.walker(range);
      var n = null;
      var annos = {};

      while (n = w.next() != null) {
        
          
      }

      return annos;

    };
    
    /**Convert a W3C Range object to start and end offset in pure text
    */
    CKEDITOR.WissKI.core.rangeToString = function( range ) {
      
      return range.select().getText();

/*      var walker = CKEDITOR.dom.walker(range);
      var str = '';
      var cur = null;
      var next = null; 
      
      // discard leading nodes that are no text nodes
      do {
        cur = walker.next();
        if (cur == null) return '';
      } while (cur.type != CKEDITOR.NODE_TEXT);
      
      str += cur.getText().substring(range.startOffset);
      
      cur = walker.next();
      next = walker.next();

      do {
        cur = walker.next();
        if (cur == null) return '';
      } while ();

      
      while (cur = walker.next() != null) console.log(cur);
*/

      

    };
 
  },


  init: function( editor ) {


    /* -- Editor-level settings & functions -- */

    editor.WissKI = editor.WissKI || {};
    editor.WissKI.core = editor.WissKI.core || {};
    // This is a list of CKEDITOR.dom.range's
    editor.WissKI.core.textrange = editor.WissKI.core.textrange || null;
    // This is an object filled with the IDs of the annotations that are
    // touched by the current selection.
    // The IDs are stored as id => id pair
    editor.WissKI.core.touched_annotations = editor.WissKI.core.touched_annotations || [];
    // This is true or false depending on the current selection incorporating text or not
    editor.WissKI.core.is_sel_collapsed = editor.WissKI.core.is_sel_collapsed || true;



    /* --- Commands --- */ 

    editor.addCommand('toggleSubject', {
      exec : function( editor ) {
        console.log('create anno', editor)
//        editor.insertHtml( '<span data-wki-subject>Here is your subject!</span>' );

        var sel = editor.getSelection();
        
        WissKI.apus.gui.showCUDDialog({
          anchor : sel,
          position : {
            target : editor.container
          }
        });
      }
    });
    
    editor.addCommand('createDescriptiveAnnotation', {
      exec : function( editor ) {
//        editor.insertHtml( '<span data-wki-subject>Here is your subject!</span>' );
        
      }
    });

    editor.addCommand('removeSpanFromAnnotation', {
      exec : function ( editor ) {
        
      }
    });

    editor.addCommand('addSpanToAnnotation', {
      exec : function ( editor ) {

      }
    });
    
/*    // helper command that calculates the selections
    editor.addCommand('_calcSelections', {
      contextSensitive : true,
      canUndo : false,
      exec : CKEDITOR.WissKI.core.calcSelections,
      refresh : CKEDITOR.WissKI.core.calcSelections
    });
*/      

    
    /* --- Buttons --- */
    
    editor.ui.addButton( 'ToggleSubject', {
      label: 'Toggle Subject',
      command: 'toggleSubject',
      toolbar: 'insert',
    });

    editor.ui.addButton( 'CreateDescrAnno', {
      label: 'Create Descriptive Annotation',
      command: 'createDescrAnno',
      toolbar: 'insert',
    });

    editor.ui.addButton( 'CreateNoteAnno', {
      label: 'Create Note',
      command: 'createNoteAnno',
      toolbar: 'insert',
    });

    editor.ui.addButton( 'DeleteAnnotation', {
      label: 'Delete Annotation',
      command: 'createReference',
      toolbar: 'insert',
    });

    

    

    /* --- Events & States --- */

    editor.on('change', function(evt) {
//      CKEDITOR.WissKI.core.calcSelections(evt.editor);
    });
    
    editor.on('selectionChange', function(evt) {
//      CKEDITOR.WissKI.core.calcSelections(evt.editor);
    });
    
    console.log('inited');

  }

});

//window.console.log("aaa");

