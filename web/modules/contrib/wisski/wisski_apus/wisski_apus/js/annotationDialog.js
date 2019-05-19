/**
*
* @author Martin Scholz, WissKI project
*/


(function($, window, Drupal, drupalSettings, undefined) {

  "use strict";
  
  drupalSettings.wisskiApus = drupalSettings.wisskiApus || {};
  drupalSettings.wisskiApus.entityPicker = {
    pipe: 'entity_picker'
  }

  Drupal.wisskiApus = Drupal.wisskiApus || {};

  /** Create a dialog object for picking entities.
  * 
  */
  Drupal.wisskiApus.dialog = function(options) {
    
    options = options || {};
    
    var defaultOptions = {
      searchTerm : '',
      show: 'slideUp',
      hide: 'slideUp',
      entityPicker: {
        pickCallback: function (uri) {},  // ensure there is at least a dummy function
        limit: 20
      },
      annoDialog: {
        currentAnno: null,
        saveCallback: Drupal.wisskiApus.setAnnotation
      }
    };
    // prepend a common prefix so that one can just use an existing id for 
    // reference
    var selector = '#' + (options.id ? options.id : 'wisski-entity-dialog-' + options.target.replace(/^#/, ''));
    var entityPicker;
    var annoDialog;
    var $dialog = $(selector);
    
    // create a hidden dialog element and append it to the body
    // taken from dialog.ajax.js
    if (!$dialog.length) {
      // Create the element if needed.
      $dialog = $('<div id="' + selector.replace(/^#/, '') + '" class="wisski-entity-dialog"/>').hide().appendTo('body');
      var epOptions = $.extend({}, options.entityPicker, { embed: $('<div class="entity-picker-dialog-wrapper subdialog-wrapper"/>').appendTo($dialog)});
      var adOptions = $.extend({}, options.annoDialog, { embed: $('<div class="anno-dialog-dialog-wrapper subdialog-wrapper"/>').appendTo($dialog)});
      entityPicker = Drupal.wisskiApus.entityPicker(epOptions);
      annoDialog = Drupal.wisskiApus.annoDialog(adOptions);
    } else {
      throw "There already exists a dialog with this ID: " + selector;
    }

    
    // create a Drupal/jquery ui dialog
    var dialogOptions = $.extend(true, {}, defaultOptions, options);
    var dialog = Drupal.dialog($dialog, dialogOptions);

    // extend the dialog object
    dialog.options = dialogOptions; // save the options
    dialog.$element = $dialog;      // direct access to the dialog's main content element
    dialog.entityPicker = entityPicker;
    dialog.annoDialog = annoDialog;
    
    /** Hide all the subdialogs so that an empty dialog appears
    */
    dialog.clear = function () {
      dialog.$element.children('.subdialog-wrapper').hide();
    };

    
    /** Open the annotation dialog and show the given annotation
    */
    dialog.showAnnotation = function (anno) {
      dialog.clear();
      dialog.$element.children('.anno-dialog-dialog-wrapper').show();
      dialog.annoDialog.doShow(anno);
      // we add the event listeners for buttons here as otherwise they do not get
      // registered correctly
      // Make entities' edit button call the entity picker
      dialog.annoDialog.$element.find('.target-list .pick-entity-button').on('click', function(e) {
        var $button = $(e.target);
        var oldUri = $button.parent().data('anno-target-uri');

        // callback when annotation has changed
        var saveCallback = function (entity) {
          // check if old and new one are identical
          var newUri = !entity ? '' : (entity.uri || '');
          if (oldUri != newUri) {
            // replace the uri in the anno object
            for (var i in dialog.annoDialog.currentAnno.target.ref) {
              if (dialog.annoDialog.currentAnno.target.ref[i] == oldUri) {
                dialog.annoDialog.currentAnno.target.ref[i] = newUri;
              }
            }
            // immediately save the annotation
            Drupal.wisskiApus.setAnnotation(dialog.annoDialog.currentAnno);
            // and update the display
            dialog.showAnnotation(dialog.annoDialog.currentAnno);
          }
        };
        // open the dialog
        dialog.searchEntities('t', saveCallback);
        // deactivate the link
        e.preventDefault();
        return false;
      });
      
      // Make target add button call the entity picker
      dialog.annoDialog.$element.find('.add-entity-button').on('click', function(e) {
        // callback when entity has been selected
        var saveCallback = function (entity) {
          // check if old and new one are identical
          var newUri = !entity ? '' : (entity.uri || '');
          if (newUri) {
            // we may need to add the target.ref structure in the
            // anno object
            if (!dialog.annoDialog.currentAnno.target) {
              dialog.annoDialog.currentAnno.target = { ref: [] };
            } else if (!dialog.annoDialog.currentAnno.target.ref) {
              dialog.annoDialog.currentAnno.target.ref = [];
            }
            // add the uri to the anno object if not present yet
            if (dialog.annoDialog.currentAnno.target.ref.indexOf(newUri) == -1) {
              // append the new one to the list
              dialog.annoDialog.currentAnno.target.ref.push(newUri);
              // immediately save the annotation
console.log("curanno, dialog",dialog.annoDialog.currentAnno);
              Drupal.wisskiApus.setAnnotation(dialog.annoDialog.currentAnno);
              // and update the display
              dialog.showAnnotation(dialog.annoDialog.currentAnno);
            }
          }
        };
        // open the dialog
        dialog.searchEntities(dialog.defaultSearchString, saveCallback);
        // deactivate the link
        e.preventDefault();
        return false;
      });

      dialog.annoDialog.$element.find('.suppress-link').on('click', function(e) {
        // deactivate the link
        e.preventDefault();
//        return false;
      });

    }

    
    /** Open the entity picker dialog and optionally perform a search
    */
    dialog.searchEntities = function (search, pickCallback) {
      
      dialog.entityPicker.pickCallback = pickCallback || dialog.entityPicker.pickCallback;
      if (typeof search === 'string') {
        search = {
          data: search
        };
      }

      dialog.clear();
      dialog.$element.children('.entity-picker-dialog-wrapper').show();
      dialog.entityPicker.doSearch(search);

    };
    
    return dialog;

  };  // end Drupal.wisskiApus.dialog()
  
  

  /** Create a dialog object for picking entities.
  * 
  */
  Drupal.wisskiApus.entityPicker = function(options) {
    
    options = options || {};
    
    var dialog;     // the object representing the dialog
    var $dialog;    // the element of the dialog's content
    var selector;   // an id for the dialog
    // the dialog's default options if nothing is set
    var defaultOptions = {
      // selector or element that this dialog is embedded in.
      // 'body' means that it is not nested in another dialog
      embed: 'body',
      searchTerm : '',
      show: 'slide',
      hide: 'slide',
      pickCallback: function (uri) {}  // ensure there is at least a dummy function
    };
    // mix the given options with the default ones
    var dialogOptions = $.extend({}, defaultOptions, options);

    if (dialogOptions.embed === 'body') {
      // prepend a common prefix so that one can just use an existing id for 
      // reference
      selector = '#' + (options.id ? options.id : 'wisski-entity-picker-' + options.target.replace(/^#/, ''));
      // create a hidden dialog element and append it to the body
      // taken from dialog.ajax.js
      $dialog = $(selector);
    }

    if (!dialog || !$dialog.length) {
      // Create the element if needed.
      var id = dialogOptions.embed === 'body' ? ' id="' + selector.replace(/^#/, '') + '"' : '';
      $dialog = $('<div' + id + ' class="wisski-entity-picker"/>');
      if (dialogOptions.embed === 'body') {
        $dialog.hide();
      }
      $dialog.appendTo(dialogOptions.embed);
      $('<form><div class="wisski-search-field"><input type="text"/></div><div class="wisski-entity-list-wrapper"/></div></form>').appendTo($dialog);
      var listWrapper = $(".wisski-entity-list-wrapper", $dialog);
      $('<div class="no-results"><p>' + Drupal.t("No results") + '</p></div>').hide().appendTo(listWrapper);
      $('<div class="loading throbber"><p>' + Drupal.t("Loading...") + '</p></div>').hide().appendTo(listWrapper);
      var list = $('<div class="wisski-anno-entity-list"/>').hide().appendTo(listWrapper);
      list.accordion({
        active: false,
        collapsible: true,
        heightStyle: 'fill'
      });
    }

    if (dialogOptions.embed === 'body') {
      // if this dialog is not embedded in another --- ie. when it is appended
      // to the body --- we create a Drupal/jquery ui dialog.
      dialog = Drupal.dialog($dialog, dialogOptions);
    } else {
      dialog = {};
    }
    
    // set some object members that must be available
    // whether nested or not
    dialog.$element = $dialog;
    dialog.pickCallback = dialogOptions.pickCallback;
  
    /** Perform an ajax search for some entities and display the result list.
    */
    dialog.doSearch = function (search, noFocus) {

      var $textInput = dialog.$element.find('.wisski-search-field input');
      $textInput.val(!search ? '' : search.data);
      
      dialog.$element.find('.wisski-entity-list-wrapper div').hide();
      // remove all entities from list
      dialog.showEntities(null, noFocus);
      
      if (!!search) {
        this.$element.find('.no-results').hide();
        this.$element.find('.loading').show();

        if (!search.pipe) {
          search.pipe = drupalSettings.wisskiApus.entityPicker.pipe
        }
        
        var ajaxSettings = {
          url: Drupal.url('/wisski/apus/pipe/analyse'),
          data: {
            query: search
          },
          success: function (data, textStatus, jqXHR) {
            dialog.$element.find('.loading').hide();
            if (!data.data) {
              data.data = {};
            }
            dialog.showEntities(data.data, noFocus);
          },
          error: function (jqXHR, textStatus, errorThrown) {
            if (console) console.log('could not search for entities:', textStatus, errorThrown);
            dialog.$element.find('.loading').hide();
            dialog.showEntities({}, noFocus);
          }
        };
        $.ajax(ajaxSettings);

      }

    };
    
    /** Removes all entries from the entities list or adds the given entities
    * to the list.
    * If the argument is an array, the items will be appended to the list; 
    * otherwise the list will be truncated.
    */
    dialog.showEntities = function (entities, noFocus) {
      
      var ul = this.$element.find('.wisski-anno-entity-list');
      if (entities === null || !Array.isArray(entities)) {
        // reset entity list
        ul.html('');
      } else {
        // add entities to the list
        for (var i in entities) {
          if (!entities.hasOwnProperty(i)) continue;
          var header = $('<h3/>')
            .data('uri', entities[i]['uri'])
            .append($('<a class="suppress-link label"/>')
              .text(entities[i]['label'])
              .attr('title', entities[i]['label'])
              .on('click', function () {
                var uri = $(this).parent().data('uri');
                dialog.pickCallback({uri: uri});
              })
            )
            .append('<span class="info">?</span>')
            .appendTo(ul);
          var $content = $('<div class="content"/>').appendTo(ul).hide();
          $content.once('show', function () {;
            // the target info is fetched via ajax
            // prepare ajax request settings
            ajaxSettings = {
              // TODO: we append a random number as server caches too
              // aggressively otherwise
              url: drupalSettings.wisskiApus.infobox.contentCallbackURL + "/" + Math.floor(Math.random() * 10000000),
              data: {
                anno: {
                  target: {
                    ref: [ entities[i]['uri'] ]
                  }
                }
              },
              dataType: 'html', // we expect html from the server
            }
            // start the request
            xhr = $.ajax(ajaxSettings)
                  .done(function (data, status, jqXHR) {
                    $content.html(data);
                  })
                  .fail(function (jqxhr, status, error) {
                    var errorMsg = "An error occurred while fetching data: (" + status + ") " + error;
                    $content.html('').text(errorMsg);
                  });
          });

         
        }
      }
      // update the accordion and show/hide the "no results" label
      ul.accordion('refresh');
      if (ul.children().length == 0) {
        ul.hide();
        this.$element.find('.no-results').show();
      } else {
        this.$element.find('.no-results').hide();
        ul.show();
      }
      
      // set focus to the text search field
      if (!noFocus) {
        // taken from http://stackoverflow.com/questions/511088/use-javascript-to-place-cursor-at-end-of-text-in-text-input-element
        var input = this.$element.find('.wisski-search-field input')[0];
        var val = input.value;
        input.focus();
        input.value = '';
        input.value = val;
      }

    };

    /** Change the value of the search term input field.
    * This also triggers a new search (if the value changed)
    */
    dialog.updateSearchTerm = function (term) {
      var $textInput = dialog.$element.find('.wisski-search-field input');
      $textInput.val(term);
      $textInput.trigger('change');
    }
    
    // React on changes in the search term field
    // but use Drupal.debounce to only trigger a search each 500ms.
    $dialog.find('.wisski-search-field input').on('keyup change', Drupal.debounce(function(evt) {
      var term = $(evt.target).val();
      term = term.length ? term : null;
      if (term == dialog.searchTerm) return;
      dialog.searchTerm = term;
      var search = {
        data: term,
        pipe: drupalSettings.wisskiApus.entityPicker.pipe
      };
      dialog.doSearch(search);
    }, 500, false));
    
    return dialog;
  };


  /** Create a dialog object for displaying annotation information
  * and simple editing functionality.
  * 
  */
  Drupal.wisskiApus.annoDialog = function(options) {
    
    options = options || {};
    
    var dialog;     // the object representing the dialog
    var $dialog,    // the element of the dialog's content
        $targets,   // the element that holds the target refs info
        $targetType,// the element that holds the target type
        $annoInfo;  // the element that holds other annotation info
    var selector;   // an id for the dialog
    // the dialog's default options if nothing is set
    var defaultOptions = {
      // selector or element that this dialog is embedded in.
      // 'body' means that it is not nested in another dialog
      embed: 'body',
      searchTerm : '',
      show: 'slide',
      hide: 'slide',
      selectEntityCallback: function (callback) {}  // ensure there is at least a dummy function
    };
    // mix the given options with the default ones
    var dialogOptions = $.extend({}, defaultOptions, options);

    if (dialogOptions.embed === 'body') {
      // prepend a common prefix so that one can just use an existing id for 
      // reference
      selector = '#' + (options.id ? options.id : 'wisski-anno-dialog-' + options.target.replace(/^#/, ''));
      // create a hidden dialog element and append it to the body
      // taken from dialog.ajax.js
      $dialog = $(selector);
    }

    if (!dialog || !$dialog.length) {
      // Create the element if needed.
      var id = dialogOptions.embed === 'body' ? ' id="' + selector.replace(/^#/, '') + '"' : '';
      $dialog = $('<div' + id + ' class="wisski-anno-dialog"/>');
      if (dialogOptions.embed === 'body') {
        $dialog.hide();
      }
      
      /* The dialog structure is as follows: 
      *  div.wisski-anno-dialog - main element ($main)
      *  - h3.targets - the accordion title for the div
      *  - div.targets - holds all annotation targets (this gets cleared up on each call)
      *  - - h4.target-ref
      *  - - div.target-ref - holds a single annotation target (multiple: one for each target)
      *  - h3.target-type
      *  - div.target-type - holds the target type (only if no target ref is present)
      *  - h3.anno-info
      *  - div.anno-info - holds other info about the annotation
      *  - - div.anno-id - holds the annotation id
      *  - - div.certainty - holds the certainty that is attributed to the annotation as a whole
      */
      $dialog.appendTo(dialogOptions.embed);
      $('<h3 class="targets" />').text(Drupal.t('Target information')).appendTo($dialog);
      $targets =    $('<div class="targets" />').appendTo($dialog);
      // the target type section is currently hidden
//      $('<h3 class="target-type" />').text(Drupal.t('Target type')).appendTo($dialog).hide();
//      $targetType = $('<div class="target-type" />').appendTo($dialog).hide();
      $('<h3 class="anno-info" />').text(Drupal.t('Annotation')).appendTo($dialog);
      $annoInfo =   $('<div class="anno-info" />').appendTo($dialog);
      
      // build the anno-info section
      $annoInfo
        // anno-id
        .append($('<div class="anno-id">')
          .append($('<label>').text(Drupal.t('ID') + ':'))
          .append($('<a class="suppress-link">'))
        )
         .append($('<div class="certainty">')
          .append($('<label>').text(Drupal.t('Certainty') + ':'))
          .append($('<select>'))
        );
      // certainty scale
      var certaintyScale = {
        certain : 'certain',
        uncertain : 'uncertain',
        speculative : 'speculative',
      };
      var $select = $annoInfo.find('.certainty select');
      $.each(certaintyScale, function(val) {
        $('<option />').val(val).text(this).appendTo($select);
      });

      $dialog.accordion({
        active: 0,
        collapsible: false,
        icons: {
          activeHeader: 'ui-icon-info',
          header: 'ui-icon-info'
        },
        heightStyle: 'fill'
      });


    }

    if (dialogOptions.embed === 'body') {
      // if this dialog is not embedded in another --- ie. when it is appended
      // to the body --- we create a Drupal/jquery ui dialog.
      dialog = Drupal.dialog($dialog, dialogOptions);
    } else {
      dialog = {};
    }
    
    // set some object members that must be available
    // whether nested or not
    dialog.$element = $dialog;
    dialog.selectEntityCallback = dialogOptions.selectEntityCallback;
  
    /** Read the annotation data and display it or clear the annotation dialog.
    * If anno is null, the dialog is emptied.
    */
    dialog.doShow = function (anno) {
      
      var ajaxSettings, xhr;

      var $dialog = dialog.$element,
          $targets = $dialog.children('div.targets'),
          $annoId = $dialog.find('div.anno-info .anno-id'),
          $certainty = $dialog.find('div.anno-info .certainty');
      
      // refresh dialog / delete old anno info
      $targets.html('');

      if (!anno) {
        $dialog.hide();
        return;
      }

      if (typeof anno === 'string') {
        anno = Drupal.wisskiApus.parseAnnotation(anno);
      }
      // save anno for later reuse
      dialog.currentAnno = anno;

      
      // display the annotation id
      if (!anno.id) {
        $annoId.hide();
      } else {
        $annoId.children('a').attr('href', anno.id).text(anno.id);
        $annoId.show();
      }
      
      // display certainty info for the annotation
      if (!anno.certainty) {
        $certainty.hide();
      } else {
        var certainty = anno.certainty;
        $certainty.children('select').prop('selected', anno.certainty);
        $certainty.show();
      }

      /*
      // display the target type/class
      if (!!anno.target.type) {
        var typeName = Drupal.wisskiApus.getTargetTypeDisplayName(anno.target.type);
        $('<p>') 
          .appendTo($dialog.children('.anno-target-type'))
          .text(typeName)
          .before($('<label>').text(Drupal.t('Class:')));
      }
      */

      // display the referred target(s)
      // TODO: currently this should be only one! eventually extend to multiple targets
      var $refFrame = $dialog.children('.anno-target-ref');
      var $refs = $('<div class="target-list wisski-anno-entity-list"/>')
            .appendTo($targets);
      if (!!anno.target.ref) {
        for (var i in anno.target.ref) {
          var ref = anno.target.ref[i];
          var $title = $('<h3>')
                .attr('data-anno-target-uri', ref)
                .append($('<span class="label">').text(ref))
                .appendTo($refs);
          var $content = $('<div>')
                .appendTo($refs)
                .append($('<p class="wait throbber">').text(Drupal.t("Loading ...")));
          var t_edit = Drupal.t('Edit');
          var $edit = $('<a class="suppress-link pick-entity-button" href="">')
                .attr('alt', t_edit).attr('title', t_edit)
                .append($('<span class="ui-icon ui-icon-pencil">'))
                .appendTo($title);
          // TODO: fetch the anno label via ajax

          // the target info is fetched via ajax
          // prepare ajax request settings
          ajaxSettings = {
            // TODO: we append a random number as server caches too
            // aggressively otherwise
            url: drupalSettings.wisskiApus.infobox.contentCallbackURL + "/" + Math.floor(Math.random() * 10000000),
            data: {
              anno: {
                target: {
                  ref: [ ref ]
                }
              }
            },
            dataType: 'html', // we expect html from the server
          }
          // start the request
          xhr = $.ajax(ajaxSettings)
                .done(function (data, status, jqXHR) {
                  $content.html(data);
                })
                .fail(function (jqxhr, status, error) {
                  var errorMsg = "An error occurred while fetching data: (" + status + ") " + error;
                  $content.html('').text(errorMsg);
                });
        }

        // the target titles are fetched via ajax
        // prepare ajax request settings
        ajaxSettings = {
          // TODO: we append a random number as server caches too
          // aggressively otherwise
          url: drupalSettings.wisskiApus.infobox.labelsCallbackURL + "/" + Math.floor(Math.random() * 10000000),
          data: {
            anno: {
              target: {
                // anno.target.ref is an array of all uris we want the titles of
                ref: anno.target.ref
              }
            }
          },
          dataType: 'json', // we expect html from the server
        }
        // start the request
        xhr = $.ajax(ajaxSettings)
              .done(function (data, status, jqXHR) {
                console.log(data);
                console.log($($refs));
                for (var uri in data) {
                  console.log($($refs).find('h3[data-anno-target-uri="' + uri + '"]'));
                  $($refs).find('h3[data-anno-target-uri="' + uri + '"] span.label').text(data[uri]);
                }
              })
              .fail(function (jqxhr, status, error) {
                if (window.console) {
                  var errorMsg = "An error occurred while fetching titles: (" + status + ") " + error;
                  console.log(errorMsg);
                }
              });


      } else {
        // there is no ref yet, so show an add button
        $('<div class="empty-target-list">')
          .appendTo($refFrame) 
          .append($('<a class="add-entity-button suppress-link" href="">').text(Drupal.t('Add...')));
      }
      // make the refs section an accordion
      $refs.accordion({
        active: 0,
        collapsible: true,
        heightStyle: content
      });
      
      
      $dialog.show();
      $dialog.accordion('refresh');

    };
    
    return dialog;
  };

})(jQuery, window, Drupal, drupalSettings);

