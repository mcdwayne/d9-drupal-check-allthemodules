/**
 * @file
 * Enables spellchecking via Yandex spellcheck service.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.ckeditor_yandex_spellchecker = {
    attach: function (context, settings) {

      CKEDITOR.on('instanceReady', function(ev) {

        // Inits spell checker bar at the bottom of ckeditor area
        let editor = ev.editor;
        if ( !editor.spellCheckInited ) {

          let editorBottomSelector = '#' + editor.id + '_bottom';
          let editorBottom = $(editorBottomSelector);
          let spellCheckBar = $('<div>').addClass('spell-check-bar');

          editorBottom.prepend(spellCheckBar);
          editor.spellCheckBar = spellCheckBar;
          editor.spellCheckInited = true;
        }

        // Removes suggestions from spell checker bar
        function clearSuggestions() {

          let suggestions = editor.spellCheckBar.find('.suggestion');

          if ( suggestions.length ){
            suggestions.each(function(num, el) {
              $(el).remove();
            });
          }
        }

        // Creates a new suggestion element for spell checker bar
        function newSuggestion(wrong, fix, text) {

          let newSugg = $('<span>');

          newSugg.addClass('suggestion');
          newSugg.text(wrong + ' ~> ' + fix);

          $(newSugg).on('click', function (ev) {
            text.data = text.data.replace(wrong, fix);
            $(ev.target).remove();
          });

          return newSugg;
        }

        // Refreshes suggestion elements in the spell checker bar
        function updateSuggestions(data, focusNode) {

          clearSuggestions();

          if ( data.length ){
            data.forEach(function( item ){
              if ( item.s.length ){
                let newSugg = newSuggestion(item.word, item.s[0], focusNode);
                $(editor.spellCheckBar).append(newSugg);
              }
            });
          }
        }

        // Does all these spell checking things.
        function spellCheck() {

          clearSuggestions();

          let sel = editor.getSelection();
          if ( !sel ){ return; }

          let nativeSel = sel._.cache.nativeSel || null;
          if ( !nativeSel ){ return; }

          let focusNode = nativeSel.focusNode;
          if ( !focusNode ){ return; }

          let text = focusNode.data;
          if ( !text ){ return; }

          $.get('http://speller.yandex.net/services/spellservice.json/checkText?text=' + text,function (data) {
            updateSuggestions(data, focusNode)
          });
        }

        editor.document.on('mouseup', spellCheck );
        editor.on('change', spellCheck );
      });
    }
  };
})(jQuery, Drupal);
