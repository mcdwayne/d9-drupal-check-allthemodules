/* WissKI Annotation Processing Unified Stratum
*
* Javascript functions that handle 
* 
* @author Martin Scholz
*/

(function ($) {
  
/* **************** *
* init              *
****************** */

window.WissKI = window.WissKI || {};
WissKI.apus = WissKI.apus || {};
WissKI.apus.ws = WissKI.apus.ws || {};
WissKI.apus.dialog = WissKI.apus.dialog || {};

  
/* **************** *
* dialogs           *
****************** */


/*
* Select Entity
*/

WissKI.apus.dialog._dialogsRaw = 
'<div id="wisski-apus-dialogs">' +
// select Entity
'<div id="wisski-apus-dialog-select-entity">' + 
'  <div class="wisski-apus-search-filter">' +
'    <form>' +
'      <input type="text" class="wisski-apus-search-term" placeholder="type..."></input>' +
'      <input type="button" class="wisski-apus-search-term-submit" value="Search"></input>' +
'      <input type="button" class="wisski-apus-select-mode"></input>' +
'      <input type="button" class="wisski-apus-select-group"></input>' +
'    </form>' +
'  </div>' +
'  <div class="wisski-apus-entity-list-area">' +
'    <p class="wisski-apus-throbber">Please wait ...</p>' +
'    <ul class="wisski-apus-entity-list">' +
'    </ul>' +
'  </div>' +
'</div>' +
// end select Entity
'</div>';

WissKI.apus.dialog._dialogsHtml = $(WissKI.apus.dialog._dialogsRaw);

WissKI.apus.dialog.selectEntity = {
  
  open: function(callback, options) {
    
    // default settings that may be overridden
    var defaults = {
      modal: true,
      dialogClasses: [],
      autoOpen: false,
      searchOnType: true,
      termValue: '',
      groupValue: '',
      modeValue: 'starts',
      groups: 'all,person,place,time,event,org',
      modes: 'exact,contains,starts',
    };
    
    // settings that cannot be overridden
    var overrides = {
      open: function(event) {
        
        var dialog = $(this).dialog();
        var dialogOpts = $(this).dialog('option');
        
        // add shortcuts for dialog elements
        dialog._searchField = $('.wisski-apus-search-term', dialog);
        dialog._groupButton = $('.wisski-apus-select-group', dialog);
        dialog._modeButton = $('.wisski-apus-select-mode', dialog);
        dialog._submitButton = $('.wisski-apus-search-term-submit', dialog);
        dialog._throbber = $('.wisski-apus-throbber', dialog);
        dialog._resultList = $('.wisski-apus-entity-list', dialog);
        dialog._lastSearch = {};
        dialog._searchID = 0; // a search request counter that is incremented

        // init search, group and mode buttons
        dialog._submitButton.button({
          icons: { primary : 'ui-icon-search' },
          text: false
        });
        dialog._modeButton.button();
        dialog._groupButton.button();

        dialog._modeButton.val(dialogOpts.modeValue);


        
        // set event handlers for updating search results
        // .on() is of jquery 1.7, D7 uses 1.4, so we have to use .bind()!

        // submitting the form would result in page reload
        $('form', dialog).bind('submit', function() { return false; });
        
        // trigger search when typing or upon enter 
        // we need to bind keyup, otherwise the typed char is not
        // yet set in the input.value
        dialog._searchField.bind('keypress', function(e) {
          if (e.keyCode == 13) {
            dialog._submitButton.click();
          }
        });
        dialog._searchField.bind('input', function(e) {
          if (dialogOpts.searchOnType) {
            dialog._submitButton.click();
          }
        });
        
        // trigger search when clicking the search button
        dialog._submitButton.bind('click', function() {
          WissKI.apus.dialog.selectEntity.doSearch(dialog);
        });

        dialog._modeButton.bind('click', function() {
          var old = dialog._modeButton.val();
          var modes = dialogOpts.modes.split(',');
          var i = modes.indexOf(old);
          if (i == modes.length - 1) {
            i = 0;
          } else {
            i++;
          }
          dialog._modeButton.val(modes[i]);
          dialog._modeButton.button("refresh");

        });

        dialog._submitButton.click();
        
      },

    }

    var dialogOpts = $.extend(true, {}, defaults, options, overrides);
    dialogOpts.dialogClass = "" + dialogOpts.dialogClasses.join(' ');

    var dialog = $(WissKI.apus.dialog._dialogsHtml)
        .find("#wisski-apus-dialog-select-entity")
        .clone()
        .dialog(dialogOpts);
        
    dialog.dialog('open');

    return dialog;

  },

  openedDialogs : [],
  
  doSearch: function(dialog, defaults) {
    
    var request = defaults !== null && typeof(defaults) === 'object' ? defaults : {
      term: dialog._searchField.val().trim()/*,
      mode: dialog._modeButton.val().split(' '),
      groups: dialog._groupButton.val().split(' ')*/
    };
    
    var changed = false;
    for (i in request) {
      if (request.hasOwnProperty(i) && (!dialog._lastSearch.hasOwnProperty(i) || request[i] != dialog._lastSearch[i])) {
        changed = true;
        break;
      }
    }
    if (!changed) {
      return;
    }

    dialog._lastSearch = request;

    if (request.term == '') {
      // hide the throbber
      dialog._throbber.toggle(false);
      dialog._resultList.empty();
      return;
    }
    
    // show the throbber
    dialog._throbber.toggle(true);
    dialog._resultList.empty();

    WissKI.apus.ws.searchEntity(
      request,
      function(resultList, id) {
        if (id == dialog._searchID) {
          WissKI.apus.dialog.selectEntity.displayResultList(dialog, resultList);
        }
      },
      ++dialog._searchID
    );

  },

  displayResultList: function(dialog, list) {
    
    dialog._throbber.toggle(false);

    var listElem = dialog._resultList;

    listElem.empty();
    $.each(list, function(k, v) {
      var liElem = $('<li>').appendTo(listElem);
console.log(k,v,liElem);
      liElem.text(v.label);
      liElem.data('wisski-apus-entity', v.uri);
      if (v.group) {
        liElem.data('wisski-apus-group', v.group);
      }
    });

  }


};




/* **************** *
* web services      *
****************** */

WissKI.apus.ws.searchEntity = function(options, callback, id) {
  
  var term = options.term || '';

  var result = [
    { uri : 'http://ex.org/1_' + term, label : term + ' 1' },
    { uri : 'http://ex.org/2_' + term, label : term + ' 2' }
  ];
  
  window.setTimeout(function() {
    callback(result, id);
  }, Math.ceil(Math.random() * 3000));
  
};




//$(function() {
$(document).ready(function() {
  var vara = $('.wki-test-select')[0];
  vara.onclick = function() {
    WissKI.apus.dialog.selectEntity.open(function () {
      console.log(this, arguments);
    });
  };
});


})(jQuery);
