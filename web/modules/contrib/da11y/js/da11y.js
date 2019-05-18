/*!
 * @author: Pojo Team
 */
/* global jQuery, PojoA11yOptions */

( function() {
	var is_webkit = navigator.userAgent.toLowerCase().indexOf( 'webkit' ) > -1,
		is_opera = navigator.userAgent.toLowerCase().indexOf( 'opera' ) > -1,
		is_ie = navigator.userAgent.toLowerCase().indexOf( 'msie' ) > -1;

	if ( ( is_webkit || is_opera || is_ie ) && document.getElementById && window.addEventListener ) {
		window.addEventListener( 'hashchange', function() {
			var id = location.hash.substring( 1 ),
				element;

			if ( ! ( /^[A-z0-9_-]+$/.test( id ) ) ) {
				return;
			}

			element = document.getElementById( id );

			if ( element ) {
				if ( !( /^(?:a|select|input|button|textarea)$/i.test( element.tagName ) ) ) {
					element.tabIndex = -1;
				}
				element.focus();
			}
		}, false );
	}
} )();



( function( $, window, document, undefined ) {
	'use strict';
	
	var Pojo_Accessibility_App = {
		cache: {
			$document: $( document ),
			$window: $( window )
		},

		cacheElements: function() {
			this.cache.$toolbar = $( '#pojo-a11y-toolbar' );
			this.cache.$btnBackgrounGroup = this.cache.$toolbar.find( 'a[class*="da11y_bg_"]' );
			this.cache.$body = $( 'body' );
		},

		bindToolbarButtons: function() {
			var $self = this;

			function resizeFontPlus( event ) {
				event.preventDefault();

				//var MAX_SIZE = 200 ,-------------- original code
				var MAX_SIZE = 130,
					MIN_SIZE = 120,
					oldFontSize = $self.currentFontSize;

				if (MAX_SIZE > oldFontSize )
					$self.currentFontSize += 10;

				$self.cache.$body.removeClass( 'pojo-a11y-resize-font-' + oldFontSize );

				if ( 120 !== $self.currentFontSize ) {
					$self.cache.$toolbar.find( 'a.da11y_resize_font_plus' ).addClass( 'active' );
					$self.cache.$body.addClass( 'pojo-a11y-resize-font-' + $self.currentFontSize );
				} else {
					$self.cache.$toolbar.find( 'a.da11y_resize_font_plus' ).removeClass( 'active' );
				}
			}

			function resizeFontMinus( event ) {
				event.preventDefault();

				//var MAX_SIZE = 200 ,-------------- original code
				var MAX_SIZE = 130,
					MIN_SIZE = 120,
					oldFontSize = $self.currentFontSize;

				if (MIN_SIZE < oldFontSize )
					$self.currentFontSize -= 10;

				$self.cache.$body.removeClass( 'pojo-a11y-resize-font-' + oldFontSize );

				if ( 120 !== $self.currentFontSize ) {
					$self.cache.$toolbar.find( 'a.da11y_resize_font_plus' ).addClass( 'active' );
					$self.cache.$body.addClass( 'pojo-a11y-resize-font-' + $self.currentFontSize );
				} else {
					$self.cache.$toolbar.find( 'a.da11y_resize_font_plus' ).removeClass( 'active' );
				}
			}
			
			function backgrounGroup( event ) {
				event.preventDefault();
				
				var currentAction = this.classList[0].substring(9),
					isButtonActive = $( this ).hasClass( 'active' ),
					bodyClasses = {
						'grayscale': 'pojo-a11y-grayscale',
						'high_contrast': 'pojo-a11y-high-contrast',
						'negative_contrast': 'pojo-a11y-negative-contrast',
						'light': 'pojo-a11y-light-background'
					};
				
				$.each( bodyClasses, function( action, bodyClass ) {
					if ( currentAction === action && ! isButtonActive ) {
						$self.cache.$body.addClass( bodyClass );
					} else {
						$self.cache.$body.removeClass( bodyClass );
					}
				} );
				
				$self.cache.$btnBackgrounGroup.removeClass( 'active' );
				
				if ( ! isButtonActive ) {
					$( this ).addClass( 'active' );
				}
			}

			function linksUnderline( event ) {
				event.preventDefault();
				$self.cache.$body.toggleClass( 'pojo-a11y-links-underline' );
				$( this ).toggleClass( 'active' );
			}

			function readableFont( event ) {
				event.preventDefault();
				$self.cache.$body.toggleClass( 'pojo-a11y-readable-font' );
				$( this ).toggleClass( 'active' );
			}

			function reset( event ) {
				event.preventDefault();

				$self.cache.$body.removeClass( 'pojo-a11y-grayscale pojo-a11y-high-contrast pojo-a11y-negative-contrast pojo-a11y-light-background pojo-a11y-links-underline pojo-a11y-readable-font' );
				$self.cache.$toolbar.find( 'a' ).removeClass( 'active' );

				var MIN_SIZE = 120;
				$self.cache.$body.removeClass( 'pojo-a11y-resize-font-' + $self.currentFontSize );
				$self.currentFontSize = MIN_SIZE;
			}

			$self.currentFontSize = 120;
			$self.cache.$toolbar.find( 'a.da11y_resize_font_plus' ).click(resizeFontPlus);
			$self.cache.$toolbar.find( 'a.da11y_resize_font_minus' ).click(resizeFontMinus);
			$self.cache.$toolbar.find( 'a[class*="da11y_bg_"]' ).click(backgrounGroup);
			$self.cache.$toolbar.find( 'a.da11y_links_underline' ).click(linksUnderline);
			$self.cache.$toolbar.find( 'a.da11y_readable_font' ).click(readableFont);
			$self.cache.$toolbar.find( 'a.da11y_reset' ).click(reset);
		},

		
		init: function() {
			this.cacheElements();
			this.bindToolbarButtons();
		}
	};

	$( document ).ready( function( $ ) {

		var plugin;
		if(plugin = document.querySelector('#da11y-plugin')) {
			plugin.innerHTML = '<div id="da11y-toggle"></div><div id="da11y-options"></div>';
		}

		$('#da11y-toggle').attr("tabindex",0);
		
		function da11yToggle(){
			var o = $('#da11y-options');
			o.hasClass("active") ? o.removeClass("active") : o.addClass("active");

			var t = $(this);
			t.hasClass("active") ? t.removeClass("active") : t.addClass("active");
		}
		function da11yToggleActive(){
			$('#da11y-options').addClass("active");
			$('#da11y-toggle').addClass("active");
		}
		
		$('#da11y-toggle').click(da11yToggle);

		var da11y_setting = drupalSettings.da11y_setting;
		var url_title = '';
		var markap = '<nav id="pojo-a11y-toolbar" class="pojo-a11y-toolbar-left" role="navigation">';
			markap +=  '<ul class="da11y-items pojo-a11y-tools">';
			for(var key in da11y_setting) {
				if(key.indexOf('da11y_') != -1 && da11y_setting[key] != ''){
					markap += '<li><a href="#" class="' + key + '">' + da11y_setting[key] + '</a></li>';
				}
				if(key.indexOf('da11yLink_') != -1 && key.indexOf('_title') != -1 && da11y_setting[key] != ''){
					url_title = da11y_setting[key];
				}
				if(key.indexOf('da11yLink_') != -1 && key.indexOf('_url') != -1 &&  da11y_setting[key] != ''){
					markap += '<li><a href="'+ da11y_setting[key] +'" class="' + key + '">' + url_title + '</a></li>';
				}
			}
		markap += '</ul></nav>';

		$('#da11y-options').append(markap);
		
		Pojo_Accessibility_App.init();
	});

}( jQuery, window, document ) );
