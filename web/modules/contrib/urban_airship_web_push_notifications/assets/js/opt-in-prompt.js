(function ( $, drupalSettings ) {

  "use strict";

  var optin_prompt = $( '.uawn-opt-in-prompt' );
  var hide_optin_prompt = drupalSettings.urban_airship_web_push_notifications.hide_optin_prompt;
  var prompt_notifications = drupalSettings.urban_airship_web_push_notifications.prompt_notifications;
  // Values in page views:
  var page_views = parseInt( drupalSettings.urban_airship_web_push_notifications.page_views );
  var temporarily_disable = parseInt( drupalSettings.urban_airship_web_push_notifications.temporarily_disable );
  var uawn_pv = $.cookie( 'uawn_pv' );
  var uawn = $.cookie( 'uawn' );
  var uawn_cookie_params = { expires: 7, path: '/' };

  $( document ).ready(function() {

    pageViewCounter();
    initBellButton();

    // Make sure a user hasn't click Dismiss button.
    if ( prompt_notifications == 'on_page_load' ) {
      showPrompt();
    }
    else {
      if ( uawn_pv >= page_views ) {
        showPrompt();
      }
    }

  });

  // Receive web notifications
  $( '.uawn-opt-in-allow' ).on( 'click', function() {
    UA.then(function( sdk ) {
      sdk.register();
      optin_prompt.hide();
    });
  });

  // Button opt-in prompt type
  $( document.body ).on( 'click', '.uawn-button', function(e) {
    UA.then(function( sdk ) {
      sdk.register();
    });
  });

  // Hide opt-in prompt for now and make it reappear
  // after N page visits. This logic applies only when user dismisses opt-in prompt.
  $( '.uawn-opt-in-dismiss' ).on( 'click', function() {
    if ( temporarily_disable > 0 ) {
      resetPageViewCounter();
    }
    optin_prompt.hide();
  });

  function pageViewCounter() {
    // Count page views.
    if ( uawn_pv === undefined ) {
      uawn_pv = 1;
    }
    else {
      uawn_pv = parseInt( uawn_pv ) + 1;
    }
    $.cookie( 'uawn_pv', uawn_pv, uawn_cookie_params );
  }

  function resetPageViewCounter() {
    $.cookie( 'uawn', true, uawn_cookie_params );
    $.cookie( 'uawn_pv', 0, uawn_cookie_params );
  }

  // Add button prompt to the page.
  function initBellButton() {
    UA.then(function( sdk ) {
    // Only show opt-in prompt when user hasn't already subscribed
      if ( sdk.isSupported && sdk.canRegister ) {
        if ( sdk.channel === null || ( isObject( sdk.channel ) && sdk.channel.opt_in === false ) ) {
          var contents = $( '.uawn-opt-in-prompt-button' ).html();
          $( '.uawn-button' ).each(function() {
            $( this ).html( contents );
          });
        }
      }
    });
  }

  function showPrompt() {
    UA.then(function( sdk ) {
    // Only show opt-in prompt when user hasn't already subscribed
      if ( sdk.isSupported && sdk.canRegister ) {
        if ( sdk.channel === null || ( isObject( sdk.channel ) && sdk.channel.opt_in === false ) ) {
          if ( hide_optin_prompt ) {
            if ( uawn === undefined ) {
              sdk.register();
              resetPageViewCounter();
            }
            else {
              if ( temporarily_disable > 0 && uawn_pv >= temporarily_disable ) {
                sdk.register();
                resetPageViewCounter();
              }
            }
          }
          else {
            if ( uawn === undefined ) {
              optin_prompt.show();
            }
            else {
              if ( temporarily_disable > 0 && uawn_pv >= temporarily_disable ) {
                optin_prompt.show();
              }
            }
          }
        }
      }
    });
  }

  function isObject( val ) {
    if ( val === null ) { return false; }
    return ( ( typeof val === 'function' ) || ( typeof val === 'object' ) );
  }

})( jQuery, drupalSettings );
