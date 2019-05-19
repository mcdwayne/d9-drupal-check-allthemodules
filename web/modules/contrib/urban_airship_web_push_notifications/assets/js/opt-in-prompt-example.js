(function ( $, drupalSettings ) {

  "use strict";

  var _toggle = $( '#edit-hide-optin-prompt' );
  var _button_snippet = $( '#button-html-snippet' );
  var _example = $( '.uawn-opt-in-prompt-example' );

  $( document ).ready(function(e) {
    if ( _toggle.prop('checked') == true ) {
      _example.hide();
      _button_snippet.show();
    }
    else {
      _example.show();
      _button_snippet.hide();
    }
    togglePrompt( $( '#edit-optin-prompt-type--wrapper input[name=optin_prompt_type]:checked' ).val() );
  });

  $( '#edit-optin-prompt-type--wrapper input' ).on( 'click', function ( e ) {
    togglePrompt( $(this).val() );
  });

  _toggle.on( 'click', function (e) {
    if ( $(this).prop( 'checked' ) == true ) {
      _example.hide();
      _button_snippet.show();
    }
    else {
      _example.show();
      _button_snippet.hide();
    }
  });

  function togglePrompt( _type ) {
    $( '.uawn-opt-in-prompt.example' ).hide();
    $( '.uawn-opt-in-prompt.prompt-button.example' ).show();
    $( '.uawn-opt-in-prompt.prompt-' + _type + '.example' ).show();
    if ( _type == 'button' ) {
      $( '.uawn-opt-in-prompt.prompt-button.example .descr' ).show();
      $( '.uawn-opt-in-prompt.prompt-button.example .descr.optional' ).hide();
    }
    else {
      $( '.uawn-opt-in-prompt.prompt-button.example .uawn-button-snippet .descr' ).hide();
      $( '.uawn-opt-in-prompt.prompt-button.example .uawn-button-snippet .descr.optional' ).show();
    }
  }

})( jQuery, drupalSettings );
