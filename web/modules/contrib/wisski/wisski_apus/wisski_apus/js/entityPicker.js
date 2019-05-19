/**
*
* @author Martin Scholz, WissKI project
*/


(function($, window, Drupal, drupalSettings, undefined) {

  "use strict";

  Drupal.wisskiApus = Drupal.wisskiApus || {};

  /** Create a dialog object for picking entities.
  * 
  */
  Drupal.wisskiApus.entityPicker = function(options) {
    
    options = options || {};

    // prepend a common prefix so that one can just use an existing id for 
    // reference
    var selector = '#' + (options.id ? options.id : 'wisski-entity-picker-' + options.target.replace(/^#/, ''));

    // create a hidden dialog element and append it to the body
    // taken from dialog.ajax.js
    var $dialog = $(selector);
    if (!$dialog.length) {
      // Create the element if needed.
      $dialog = $('<div id="' + selector.replace(/^#/, '') + '" class="wisski-entity-picker"/>').hide().appendTo('body');
      $('<form><div class="wisski-search-field"><input type="text"/></div><div class="wisski-entity-list-wrapper"/></div></form>').appendTo($dialog);
      var listWrapper = $(".wisski-entity-list-wrapper", $dialog);
      $('<div class="no-results"><p>' + Drupal.t("No results") + '</p></div>').hide().appendTo(listWrapper);
      $('<div class="loading throbber"><p>' + Drupal.t("Loading...") + '</p></div>').hide().appendTo(listWrapper);
      var list = $('<div class="entity-list"/>').hide().appendTo(listWrapper);
      list.accordion({
        active: false,
        collapsible: true,
        heightStyle: 'fill'
      });
    }
    
    var defaultOptions = {
      searchTerm : '',
      show: 'slide',
      hide: 'slide',
      pickCallback: function (uri) {}  // ensure there is at least a dummy function
    };

    var dialogOptions = $.extend({}, defaultOptions, options);
    
    var dialog = Drupal.dialog($dialog, dialogOptions);
    dialog.$element = $dialog;
    dialog.pickCallback = dialogOptions.pickCallback

    dialog.searchEntities = function (search) {
      
      dialog.$element.find('.wisski-entity-list-wrapper div').hide();
      
      // remove all entities from list
      dialog.showEntities(null);
      
      if (!!search) {
        this.$element.find('.loading').show();
        
        var ajaxSettings = {
          url: Drupal.url('/wisski/apus/pipe/analyse'),
          data: {
            query: search 
          },
          success: function (data, textStatus, jqXHR) {
            dialog.$element.find('.loading').hide();
            if (!data.data) {
              data = {};
            }
            dialog.showEntities(data.data);
          },
          error: function (jqXHR, textStatus, errorThrown) {
            if (console) console.log('could not search for entities:', textStatus, errorThrown);
            dialog.$element.find('.loading').hide();
            dialog.showEntities({});
          }
        };
        $.ajax(ajaxSettings);

      }

    };

    dialog.showEntities = function (entities) {
      
      var ul = this.$element.find('.entity-list'),
        li;
console.log("se", entities);
      if (entities === null || !Array.isArray(entities)) {
        // reset entity list
        ul.html('');
      } else {
        // add entities to the list
        for (var i in entities) {
          if (!entities.hasOwnProperty(i)) continue;
          $('<h3/>')
            .data('uri', entities[i]['uri'])
            .append($('<a class="label"/>')
              .text(entities[i]['label'])
              .attr('title', entities[i]['label'])
              .on('click', function () {
                var uri = $(this).parent().data('uri');
                dialog.pickCallback({uri: uri});
              })
            )
            .append('<a class="info">?</a>')
            .appendTo(ul);
          $('<div/>').html(entities[i]['content']).appendTo(ul);
        }

      }

      ul.accordion('refresh');

      if (ul.children().length == 0) {
        ul.hide();
        this.$element.find('.no-results').show();
      } else {
        this.$element.find('.no-results').hide();
        ul.show();
      }

    };


    dialog.updateSearchTerm = function (term) {
      var $textInput = dialog.$element.find('.wisski-search-field input');
      $textInput.val(term);
      $textInput.trigger('change');
    }
    
    // react on changes in the search term field
    // but use the Drupal.debounce to only trigger a search each 500ms
    $dialog.find('.wisski-search-field input').on('keyup change', Drupal.debounce(function(evt) {
      var term = $(evt.target).val();
      term = term.length ? term : null;
      if (term == dialog.searchTerm) return;
      dialog.searchTerm = term;
      var search = {
        data: term,
        pipe: 'entity_picker' //drupalSettings.wisskiApus.entityPicker.pipe
      };
      dialog.searchEntities(search);
    }, 500, false));
    
    return dialog;
  };


})(jQuery, window, Drupal, drupalSettings);

