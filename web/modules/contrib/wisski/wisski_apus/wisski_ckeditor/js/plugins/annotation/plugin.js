/**
 * @file
 * Drupal Image plugin.
 *
 * This alters the existing CKEditor image2 widget plugin to:
 * - require a data-entity-type and a data-entity-uuid attribute (which Drupal
 *   uses to track where images are being used)
 * - use a Drupal-native dialog (that is in fact just an alterable Drupal form
 *   like any other) instead of CKEditor's own dialogs.
 *
 * @see \Drupal\editor\Form\EditorImageDialog
 *
 * @ignore
 */

(function ($, Drupal, CKEDITOR) {

  'use strict';


  CKEDITOR.plugins.add('wisski_annotation', {

    init: function (editor) {

      var myself = this;
      
      var annoTag = 'span';

      editor.addContentsCss(myself.path + "anno.css");
      
      editor.addCommand('wisskiSaveAnnotation', {
        
        modes: {wysiwyg: 1, source: 1},
        canUndo: true,
        exec: function (editor, anno) {
          
console.log("save annotation: ", anno);

          var range = null;

          // check if we have target information
          // otherwise an annotation doesn't make sense
          if (!anno.target) {
            throw "Cannot save annotation: no target information";
          }

          // check if we can locate the body range
          // if there is an ID, we try to find an existing element
          // that contains the annotation
          if (!!anno.id) {
            // try to restore the $elements array of annotation elements
            var elements = editor.document.find('[data-wisski-anno-id="' + anno.id + '"], #' + anno.id);
            if (!anno.body) anno.body = {};
            anno.body.$elements = [];
            for (var i in elements) {
              anno.body.$elements.push($(elements[i].$));
            }
          }
          if (!!anno.body && !!anno.body.$elements) {
            // nothing to do!?
          } else if (!anno.body) {
            throw "Cannot save annotation: no body information";
          } else if (!!anno.body.selection) {
            range = anno.body.selection.getRanges(1)[0];
          } else if (!!anno.body.domRange) {
            range = anno.body.domRange
          } else if (!!anno.body.textRange) {
            range = myself.createDomRangeFromTextRange(editor, anno.body.textRange[0], anno.body.textRange[1]);
console.log('range', range, anno.body.textRange);
          } else {
            throw "Cannot save annotation: no body range information";
          }
          
          // Support Undo
          editor.fire('saveSnapshot');
          
          
          // delete all other annotations that overlap with this one
          // TODO: make annos overlappable
          var otherAnnoIds = myself.getIdsOfAnnotationsInRange(editor, range);
          editor.execCommand('wisskiDeleteAnnotation', {'ids' : otherAnnoIds});

          // insert an element as this is a new anno
          if (!anno.body.$elements) {
            range.optimize();
            var element = editor.document.createElement(annoTag);
            var innerHtml = range.extractContents(false, false);
            element.append(innerHtml);
            range.insertNode(element);
            anno.body.$elements = $(element.$);
          }
          
          Drupal.wisskiApus.setAnnotation(anno, anno.body.$elements[0]);

          // we are finished, release selection and add a new undo point 
          editor.fire('saveSnapshot');

        }

      });


      editor.addCommand('wisskiAddAnnotation', {
        allowedContent: 'span',
        requiredContent: 'span',
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor, data) {
          
          var anno = {
            id: 'wta' + myself.createUUID4() + "beegaway",
            body : { 
              domRange: editor.getSelection().getRanges(1)[0]
            },
            target: {
              cat : "person",
              ref: [ "http://ex.org/p2", "http://ex.org/p1" ]
            }
          };
          
          editor.execCommand('wisskiSaveAnnotation', anno);
         
        }
      });


      editor.addCommand('wisskiDeleteAnnotation', {
        allowedContent: 'span',
        requiredContent: 'span',
        modes: {wysiwyg: 1 },
        canUndo: true,
        exec: function (editor, data) {
          
          var ids, 
              spans = [];

          if (!data || !data.ids) { 
            ids = myself.getIdsOfAnnotationsInRange(editor, editor.getSelection().getRanges()[0]);
          } else if (CKEDITOR.tools.isArray(data.ids))  {
            ids = data.ids;
          } else {
            ids = [data.ids];
          }

          if (ids.length == 0) return;
          
          for (var id in ids) {
            spans = spans.concat(myself.getSpansOfAnno(editor, ids[id]));
          }

          if (spans.length == 0) return; // this may happen if an id is passed but there are no spans with that id
          
          // we begin altering the text => add a new undo point 
          editor.fire('saveSnapshot');

          $.each(spans, function (i, span) {
            span.remove(true);
          });

          // we are finished => add a new undo point 
          editor.fire('saveSnapshot');

        }
      });


      if (editor.ui.addButton) {
        editor.ui.addButton('wisskiAddAnnotation', {
          label: Drupal.t('Annotate'),
          command: 'wisskiAddAnnotation',
          icon: this.path + '/annotation.png'
        });
       editor.ui.addButton('wisskiDeleteAnnotation', {
          label: Drupal.t('Delete annotation'),
          command: 'wisskiDeleteAnnotation',
          icon: this.path + '/delete.png'
        });
      }

/* this fails as the behavior cannot be attached to the editor's dom
      editor.on('selectionChange', function () {
        Drupal.behaviors.wisskiInfobox.attach($(editor.elementPath().root.$), drupalSettings);
      });
*/

    },

    
    createUUID4 : function() {
      return Drupal.wisskiApus.createUuid4();
    },
    
    
    /** Returns all spans/elements that belong to the annotation with the given id
    *
    * @return an array of 
    */
    getSpansOfAnno : function(editor, id) {
      
      var nl = editor.document.find('#' + id + ', [data-wisski-anno-id="' + id + '"]'),
          nll = nl.count(),
          ret = [];

      for (var i = 0; i < nll; i++) {
        ret.push(nl.getItem(i));
      }
      return ret;
      
    },

    
    /** Returns an array of annotation IDs that have elements that are 
    * (partially) enclosed by the
    * given range and that carry annotations or parts of them.
    */
    getIdsOfAnnotationsInRange : function(editor, range) {
      
      var tmp = {}, 
          ret = [],
          node = range.getCommonAncestor(false, true);
      
      // first we look inside the range with the help of a walker
      var w = new CKEDITOR.dom.walker(range);
      w.guard = function(e) {
        // we use the guard function to collect the ids
        // as this function is reliably called
        if (!e.type || e.type != CKEDITOR.NODE_ELEMENT) return true;
        if (e.hasAttribute('data-wisski-anno-id')) {
          tmp[e.data("wisski-anno-id")] = 1;
        } else if (e.hasAttribute('data-wisski-anno') && e.getId()) {
          tmp[e.getId()] = 1;
        } else if (e.hasAttribute('data-wisski-anno')) {
          // if this is an annotation without id, set one
          var id = 'wta' + myself.createUUID4();
          e.setAttribute('id', id);
          e.setAttribute('data-wisski-anno-id', id);
          tmp[e.getId()] = 1;
        }

        return true;
      }

      w.checkForward();

      // then we have to go up the DOM tree to check whether surrounding nodes
      // contain annotations
      do {
        w.guard(node);
      } while (node = node.getParent());

      
      for (var v in tmp) {
        ret.push(v);
      }

      return ret;

    },

    
    /** Creates a CKEDITOR.dom.range from an interval given by its
    * start and end position in the pure/plain text (all tags removed).
    *
    * The range is minimal in the sense that it begins and ends with the
    * innermost text nodes. See range.optimize() if you need the start
    * and end to bubble up the DOM tree.
    */
    createDomRangeFromTextRange : function(editor, start, end) {
      
      var container = editor.editable(),
          r = new CKEDITOR.dom.range(container),
          startRemainder = start,
          endRemainder = end,
          w = new CKEDITOR.dom.walker(r),
          textNode,
          lastNode,
          len;

      r.setStartBefore(container);
      r.setEndAfter(container);
      
      // on our walk we only consider text nodes
      w.evaluator = CKEDITOR.dom.walker.nodeType(CKEDITOR.NODE_TEXT);

      // setting the range's start & startOffset
      // walk through the DOM till startRemainder is consumed
      while (textNode = w.next()) {
        len = textNode.getLength();
        if (startRemainder == 0 || startRemainder < len) {
          // len may also be 0, that's why we have this extra check.
          // otherwise, if startRemainder and len are equal and non-zero
          // we purpusely want to go in the else branch!
          r.setStart(textNode, startRemainder);
          break;
        } else { 
          // startRemainder >= len!
          // if startRemainder and len are equal, we do another hop as we
          // prefer the range to start on the beginning of a textnode rather 
          // than at the end
          startRemainder -= len;
          // we also have to consume the endRemainder!
          endRemainder -= len;
        }
      }
      
      // the document is shorter than the range start => there is no range
      if (textNode == null) return null;

      // (not used!) note that startRemainder is not totally consumed but 
      // indicates the offset now!


      // setting the range's end & endOffset.
      // further walk through the DOM till endRemainder is consumed.
      // start and end may be in a single textnode =>
      // that's why we don't start with while but with do.
      do {
        lastNode = textNode;
        len = textNode.getLength();
        if (endRemainder <= len) {
          // if endRemainder and len are equal, now we don't do another hop
          // as we prefer the range to end on the end of a textnode rather 
          // than at the beginning
          r.setEnd(textNode, endRemainder);
          break;
        } else { 
          endRemainder -= len;
        }
      } while (textNode = w.next());
      
      if (textNode == null) {
        // the document is shorter than the ranges end position =>
        // set it to the end
        textNode = lastNode;
        r.setEnd(textNode, textNode.getLength());
      }

      return r;
      
    },

    
    /** Adjust the range so that its ends are at word boundaries.
    * The range will always be expanded with one exception:
    * heading and trailing whitespace is deselected
    */
    selectFullWords: function (editor, range) {
      
      //** buggy!!! **//

      var atBoundary = false;
      var i = 0;

      editor.plugins.wisski_annotation.descendRange(range);
      
      var startText = range.startContainer.getText();
      var afterStart = startText.substring(range.startOffset);
      var beforeStart = startText.substring(0, range.startOffset);
console.log('st', afterStart, beforeStart);

      if (afterStart.length) {
        i = /^\s*/.exec(afterStart).length;
      }
      if (i) {
console.log('a', range, i);
        range.setStart(range.startContainer, range.startOffset + i);
      } else if (beforeStart.length) {
        i = /\S*$/.exec(beforeStart).length;
        if (i) {
console.log('b', range, i);
          range.setStart(range.startContainer, range.startOffset - i);
        }
      }

      var endText = range.endContainer.getText();
      var afterEnd = endText.substring(range.endOffset);
      var beforeEnd = endText.substring(0, range.endOffset);
      
      i = 0;
      if (beforeEnd.length) {
console.log('c', range, i);
        i = /^\s*/.exec(beforeEnd).length;
      }
      if (i) {
        range.setEnd(range.endContainer, range.endOffset - i);
      } else if (afterEnd.length) {
        i = /\S*$/.exec(afterEnd).length;
        if (i) {
console.log('d', range, i);
          range.setEnd(range.endContainer, range.endOffset + i);
        }
      }
      
    },
    
    /** Reposition the range so that its containers are text nodes
    *
    */
    descendRange: function (range) {
      
      //** not tested !!! **//

      while (range.startContainer.type == CKEDITOR.NODE_ELEMENT) {
        var child = range.startContainer.getChild(range.startOffset);
        range.setStart(child, 0);
      }

      while (range.endContainer.type == CKEDITOR.NODE_ELEMENT) {
        var child = range.endContainer.getChild(range.endOffset);
        range.setEnd(child, 0);
      }

    },
    
    /** Returns the text covered by the given range
    */
    getRangeText(range) {
      
      if (range.collapsed) return '';

      var fragment = range.cloneContents();
      var elem = new CKEDITOR.dom.element.createFromHtml("<div>" + fragment.getHtml() + "</div>");
      return elem.getText();

    },


  });

})(jQuery, Drupal, CKEDITOR);
