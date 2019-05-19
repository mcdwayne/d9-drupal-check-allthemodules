/**
*
* @author Martin Scholz, WissKI project
*/


(function($, window, Drupal, drupalSettings, undefined) {

  "use strict";

  Drupal.wisskiApus = Drupal.wisskiApus || {};

  
  Drupal.wisskiApus.annotationAttributes = {
    'data-wisski-anno': 1,
    'data-wisski-anno-id': 1,
    'data-wisski-type': 1,
    'data-wisski-target': 1,
    'data-wisski-cat': 1,
    'data-wisski-certainty': 1,
    'about': 1,
    'typeof': 1
  };
    
  
  // create a random level 4 UUID
  // taken from http://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid-in-javascript/2117523#2117523
  Drupal.wisskiApus.createUuid4 = function () {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
      return v.toString(16);
    });
  };

  
  /**
  *
  */
  Drupal.wisskiApus.parseAnnotation = function (idOrElement, context) {

    var anno = {
      body: {}, // filled below
      target: {} // filled below
    };
    // save the original context if it was given
    if (!!context) anno.body.context = context;
    // if context is not given we search the whole document
    context = context || window.document;
    // if idOrElement is an id it must be an element id with a hash prepended
    if (typeof idOrElement === 'string') {
      anno.id = idOrElement.replace(/^#/, '');
      anno.body.$elements = $(context).find('[data-wisski-anno-id="' + anno.id + '"], #' + anno.id);
console.log("args parsea", [idOrElement, context, $anno.body.$elements]);
    } else {
      var $element = $(idOrElement);
      // search for the annotation id in the given order: data attr, name attr, id attr
      if ($element.is('[data-wisski-anno-id]')) {
        anno.id = $element.data('wisski-anno-id');
      } else if ($element.is('[name]')) {
        anno.id = $element.attr('name');
      } else if ($element.is('[id]')) {
        anno.id = $element.attr('id');
      }
      if (!!anno.id) {
        // if the annotation has an id we check the context for other element with
        // that id to collect scattered annotations.
        // we search for elements having the data-wisski-anno-id attribute set to
        // this ID or that have an ID/name attribute with this value
        anno.body.$elements = $(context).find('[data-wisski-anno-id="' + anno.id + '"], #' + anno.id);
      } else {
        // otherwise we just add the single given element
        anno.body.$elements = $element;
      }
    }

console.log('ano', anno);
    // now we iterate over all elements and collect the attributes
    anno.body.$elements.each(function () {
      
      var $element = $(this);

      // we target an instance
      if ($element.is('[about], [data-wisski-target-ref]')) {
        anno.target.ref = $element.attr('about') || $element.attr('data-wisski-target-ref');
        anno.target.ref = anno.target.ref.split(' ');
      }

      // we also send the type / category info as this may help the
      // server to respond faster.
      // the other possibility is that the instance is not specified,
      // and there is just a type or category annotation.
      if ($element.is('[typeof]')) {
        anno.target.type = $element.attr('typeof');
      } 
      if ($element.is('[data-wisski-cat]')) {
        anno.target.type = $element.attr('data-wisski-cat');
      }
      if ($element.is('[data-wisski-certainty]')) {
        anno.certainty = $element.attr('data-wisski-certainty');
      }

    });

    return anno;
    
  }; // end parseAnnotation()
  
  
  /** Removes all possible wisski annotation attributes from the given 
  *   elements.
  *
  */
  Drupal.wisskiApus.removeAnnotation = function ($elements, removeId) {
    $elements.each(function () {
      for (var attr in Drupal.wisskiApus.annotationAttributes) {
        $(this).removeAttr(attr);
        if (!!removeId) {
          $(this).removeAttr('id');
          $(this).removeAttr('name');
        }
      }
    });
  };


  /** Sets or updates an annotation
  */
  Drupal.wisskiApus.setAnnotation = function (anno, context) {
    var attrs = {};
    
    if (!anno.target) {
      throw "No target information given";
    }

    // find out the context in case we have to do some DOM searching
    if (!context && !!anno.body) {
      if (!!anno.body.context) {
        context = anno.body.context;
      } else if (!!anno.body.$elements) {
        // check if all elements have the same owner doc
        // then we can set it as context
        anno.body.$elements.each(function () {
          if (!context) {
            context = $(this).prop('ownerDocument');
          } else if (context !== $(this).prop('ownerDocument')) {
            // elements have different owner docs =>
            // unset context and break loop
            context = null;
            return false;
          }
        });
      }
    }
    if (!context) {
      // last resort: take the main document
      context = window.document;
    }
    
    // check and determine which elements should carray the annotation:
    // either the elements are directly given in the $elements member which is
    //   a jQuery result list
    // or an id is given
    if (!anno.body || !anno.body.$elements) {
      if (!anno.body) anno.body = {};
      if (!!anno.id) {
        var $elements = $(context).find('[data-wisski-anno-id="' + anno.id + '"], #' + anno.id);
        anno.body.$elements = $elements;
      }
    }
    if (!anno.body.$elements) {
      throw "No body element(s) given";
    }
    // we always set an id for better handling
    // and splitting of annotation elements.
    if (!anno.id) {
      anno.id = "wta" + Drupal.wisskiApus.createUuid4(); // TODO: call id generation function
    }
    
    // cleanse the elements from goneby annotations or old values
    Drupal.wisskiApus.removeAnnotation(anno.body.$elements);
  
    // we set a marker for the annotation that it is thought of as being 
    // oac compatible.
    attrs['data-wisski-anno'] = 'oac';
    // we only set the id on the data-... attribute, not on the id attribute
    attrs['data-wisski-anno-id'] = anno.id;
    // the target category
    // the category is meant to function as a coarse classification of 
    // the annotation target
    if (!!anno.target.cat) {
      attrs['data-wisski-cat'] = anno.target.cat;
    }
    // the target type -- this is the class uri
    // we reuse the RDFa typeof attribute
    if (!!anno.target.type) {
      attrs['typeof'] = anno.target.type;
    }
    // set the targets
    // we set them in two attributes:
    // we reuse the RDFa about attribute for all targets.
    // we use the data-wisski-target(-*) attributes for more fine-grained
    // description of the reference type
    if (!!anno.target.ref) {
      for (var k in anno.target.ref) {
        // TODO: not all reference of any types may make sense to put in the about attrib
        // eg. a note reference rather implies "inverse" about
        var values = anno.target.ref;
        if (values && values.constructor === Array) {
          values.join(' ');
        } else if (values) {
          values = values.toString();
        } else {
          continue;
        }
        attrs['about'] = values;
        attrs["data-wisski-target-ref"] = values;
      }
    }
    // the certainty
    // this specifies the certanty of the whole annotation
    if (!!anno.certainty) {
      attrs['data-wisski-certainty'] = anno.certainty;
    }
      
    // At last we set the attributes to the elements
    anno.body.$elements.each(function () {
console.log("attrs", attrs);
      $(this).attr(attrs);
    });

  };


  /** Returns the displayable name of a annotation target type
  */
  Drupal.wisskiApus.getTargetTypeDisplayName = function (type) {
    return type;
  };

})(jQuery, window, Drupal, drupalSettings);



