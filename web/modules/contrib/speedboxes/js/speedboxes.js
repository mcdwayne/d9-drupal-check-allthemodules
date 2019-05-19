window.loadSpeedboxes = function( $ ) {

  $( function() {
    // Mouse coordinates in arrays are always X, Y
    var bMouseDown          = false;
    var bMouseMoved         = false;
    var aMouseStartPosition = [ 0, 0 ];
    var aMouseLastPosition  = [ 0, 0 ];
    var pSelectionRect      = null;
    var pSelectionContainer = null;
    var pSelectedCheckboxes = null;
    var pEditingPopup       = null;

    if( !window.speedboxes )
      window.speedboxes = {};

    if( !speedboxes.config )
      speedboxes.config = {};

    if( !speedboxes.config.localization )
      speedboxes.config.localization  = {};

    if( !speedboxes.config.ignore_elements )
      speedboxes.config.ignore_elements  = "input,select";

    if( !speedboxes.config.localization.check_all )
      speedboxes.config.localization.check_all    = "Check all";
    if( !speedboxes.config.localization.uncheck_all )
      speedboxes.config.localization.uncheck_all  = "Uncheck all";
    if( !speedboxes.config.localization.reverse )
      speedboxes.config.localization.reverse      = "Check reverse";

    var bEnabled            = true;
    var aVersion            = $.fn.jquery.split( "." );
    if( aVersion[0]=="1" && (parseInt(aVersion[1])<4 || (aVersion[1]=="4" && parseInt(aVersion[2])<3)) ) {
      bEnabled  = false;
      console.warn( 'Speedboxes requires jQuery 1.4.3 or later.' );
    }

    window.toggleSpeedboxes = function() {
      if( bEnabled ) {
        bMouseDown  = false;
        bMouseMoved = false;
        aMouseStartPosition = [ 0, 0 ];

        if( pSelectedCheckboxes ) {
          pSelectedCheckboxes.removeClass( 'speedboxes-selected' );

          pSelectedCheckboxes = $();
        }

        if( pEditingPopup )
          pEditingPopup.hide();

        bEnabled  = false;
      }
      else
        bEnabled  = true;
    };

    function createSelectionContainer() {

      return $( '<div id="speedboxes-selection" class="speedboxes speedboxes-selection"></div>' )
        .css( { position:'absolute' } )
        .appendTo( $('body') );

    }

    function performAction( e ) {

      var pElement  = $(e.target);
      var sAction   = pElement.data( "speedboxes-action" );

      if( sAction=="check_all" )
        pSelectedCheckboxes.attr( "checked", "checked" );
      else if( sAction=="uncheck_all" )
        pSelectedCheckboxes.removeAttr( "checked" );
      else if( sAction=="reverse" ) {
        pSelectedCheckboxes.each( function() {
          var pSelf = $(this);
          if( pSelf.is(':checked') )
            pSelf.removeAttr( "checked" );
          else
            pSelf.attr( "checked", "checked" );
        } );
      }

      updateEditingPopup();

      return false;

    }

    function createEditingPopupAction( sAction ) {

      return $( '<a href="#" class="speedboxes-action speedboxes-action-'+sAction.replace( /([^a-zA-Z0-9-]+)/g, '-' )+'"></a>' )
        .text( speedboxes.config.localization[sAction] )
        .data( 'speedboxes-action', sAction )
        .mousedown( performAction )
        .click( function(e) { return false; } );

    }

    function createEditingPopup() {

      var pContainer  = $( '<div id="speedboxes-popup" class="speedboxes speedboxes-popup"></div>' )
        .css( { position:'absolute' } )
        .appendTo( $('body') );

      createEditingPopupAction( 'check_all' )
        .appendTo( pContainer );

      createEditingPopupAction( 'uncheck_all' )
        .appendTo( pContainer );

      createEditingPopupAction( 'reverse' )
        .appendTo( pContainer );

      return pContainer;

    }

    function getSelectionRect() {

      return {
        top     : aMouseStartPosition[1] > aMouseLastPosition[1] ? aMouseLastPosition[1] : aMouseStartPosition[1],
        right   : aMouseStartPosition[0] > aMouseLastPosition[0] ? aMouseStartPosition[0] : aMouseLastPosition[0],
        bottom  : aMouseStartPosition[1] > aMouseLastPosition[1] ? aMouseStartPosition[1] : aMouseLastPosition[1],
        left    : aMouseStartPosition[0] > aMouseLastPosition[0] ? aMouseLastPosition[0] : aMouseStartPosition[0],
      };

    }

    function updateEditingPopup() {

      if( !pEditingPopup )
        return;

      if( pSelectedCheckboxes.not(':checked').length )
        pEditingPopup.find( '.speedboxes-action-check-all' ).removeClass( 'speedboxes-active-action' );
      else
        pEditingPopup.find( '.speedboxes-action-check-all' ).addClass( 'speedboxes-active-action' );

      if( pSelectedCheckboxes.filter(':checked').length )
        pEditingPopup.find( '.speedboxes-action-uncheck-all' ).removeClass( 'speedboxes-active-action' );
      else
        pEditingPopup.find( '.speedboxes-action-uncheck-all' ).addClass( 'speedboxes-active-action' );

    }

    function updateSelectedCheckboxes() {

      var pOldSelection   = pSelectedCheckboxes;
      pSelectedCheckboxes = $();

      $('input:checkbox:enabled:visible').each( function() {

        pCheckbox = $(this);
        var pPosition = pCheckbox.offset();
        if( pPosition.left>=pSelectionRect.left && pPosition.left<=pSelectionRect.right &&  pPosition.top>=pSelectionRect.top && pPosition.top<=pSelectionRect.bottom ) {
          pSelectedCheckboxes = pSelectedCheckboxes.add( pCheckbox );
        }

      } );

      if( pOldSelection ) {
        var pRemoved  = pOldSelection
          .not( pSelectedCheckboxes )
          .removeClass( 'speedboxes-selected' );

        var pAdded    = pSelectedCheckboxes
          .not( pOldSelection )
          .addClass( 'speedboxes-selected' );

        // For the case we'd like to always show the popup...
        /*if( pRemoved.length || pAdded.length )
          updateEditingPopup();*/

      }
      else {
        pSelectedCheckboxes
          .addClass( 'speedboxes-selected' );
      }

    }

    $('body')
    .mousedown( function(e) {

      if( !bEnabled || $(e.target).is(speedboxes.config.ignore_elements) )
        return;

      bMouseDown  = true;
      bMouseMoved = false;
      aMouseStartPosition = [ e.pageX, e.pageY ];

      if( pSelectedCheckboxes ) {
        pSelectedCheckboxes.removeClass( 'speedboxes-selected' );

        pSelectedCheckboxes = $();
      }

      if( pEditingPopup )
        pEditingPopup.hide();

      return false;

    } )
    .bind( 'mousemove scroll', function(e) {

      if( bMouseDown ) {

        if( !pSelectionContainer )
          pSelectionContainer = createSelectionContainer();

        aMouseLastPosition  = [ e.pageX, e.pageY ];
        pSelectionRect      = getSelectionRect();

        if( !bMouseMoved ) {
          pSelectionContainer
            .show();
          bMouseMoved = true;
        }

        pSelectionContainer
          .css( { top:pSelectionRect.top+"px", width:(pSelectionRect.right-pSelectionRect.left)+"px", height:(pSelectionRect.bottom-pSelectionRect.top)+"px", left:pSelectionRect.left+"px" } );

        updateSelectedCheckboxes();

        return false;

      }

    } )
    .bind( 'mouseup mouseleave', function(e) {

      if( !bMouseDown )
        return;

      bMouseDown  = false;

      if( pSelectionContainer && bMouseMoved ) {
        pSelectionContainer.hide();

        if( pSelectedCheckboxes.length ) {
          if( !pEditingPopup )
            pEditingPopup = createEditingPopup();

          pEditingPopup.show();

          var pBody         = $('body');

          var iPopupWidth   = pEditingPopup.outerWidth();
          var iPopupHeight  = pEditingPopup.outerHeight();
          var iBodyWidth    = pBody.innerWidth();
          var iBodyHeight   = pBody.innerHeight();
          var aPosition     = [
            aMouseLastPosition[0]+iPopupWidth > iBodyWidth ? aMouseLastPosition[0]-iPopupWidth : aMouseLastPosition[0],
            aMouseLastPosition[1]+iPopupHeight > iBodyHeight ? aMouseLastPosition[1]-iPopupHeight : aMouseLastPosition[1]
          ];
          pEditingPopup.css( {
            left  : aPosition[0] + "px",
            top   : aPosition[1] + "px"
          } );

          updateEditingPopup();

        }

      }

      return false;

    } );

  } );

};
if( window.jQuery ) {
  window.loadSpeedboxes(jQuery);
}
else {
  window._loadSpeedboxes  = setInterval( 'if(window.jQuery){clearInterval(window._loadSpeedboxes);window.loadSpeedboxes(jQuery);window._loadSpeedboxes=null;delete window._loadSpeedboxes;}', 100 );
  document.body.appendChild( document.createElement( 'script' ) ).src = 'http://code.jquery.com/jquery-latest.js';
}
