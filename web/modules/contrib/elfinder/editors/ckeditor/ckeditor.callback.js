// $Id$

function elfinder_ckeditor_callback(arg1) {
         // console.log(typeof url);
          
          var url = arg1;
          
          if (typeof arg1 == 'object') {
            url = arg1.url;
          }
          funcNum = window.location.search.replace(/^.*CKEditorFuncNum=(\d+).*$/, "$1");
          window.opener.CKEDITOR.tools.callFunction(funcNum, url);
          window.close();
}

(function ($, Drupal, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('elfinder', {
    // Define commands and buttons
    init: function (editor) { 
      
     // alert(123);
      CKEDITOR.config.filebrowserBrowseUrl = 'http://ya.ru';
      CKEDITOR.config.filebrowserImageBrowseUrl = 'http://ya.ru';
      CKEDITOR.timestamp = 666;
      
CKEDITOR.dialog.add( 'about2', function( editor ) {
	var lang = editor.lang.about,
		imagePath = CKEDITOR.getUrl( CKEDITOR.plugins.get( 'about' ).path + 'dialogs/' + ( CKEDITOR.env.hidpi ? 'hidpi/' : '' ) + 'logo_ckeditor.png' );

	return {
		title: 'elFinder',
		minWidth: 800,
		minHeight: 600,
		contents: [ {
			id: 'tab1',
			label: '',
			title: '',
			expand: true,
			padding: 0,
			elements: [
				{
					type: 'html',
					html: '<div id="finder"></div>'
				}
			]
		} ],
		buttons: [ CKEDITOR.dialog.cancelButton ]
	};
} );

      
      editor.addCommand('elfinderimage', {
        exec: CKEDITOR.elfinder.imageDialog
      });
      editor.ui.addButton('elFinderImage', {
        label: Drupal.t('Insert images'),
        command: 'elfinderimage',
        icon: editor.config.elFinderImageIcon
      });
    }
    
    
    
  });

  /**
   * Global container for helper methods.
   */
  CKEDITOR.elfinder = CKEDITOR.elfinder || {
    
    /**
     * Opens Imce for inserting images into CKEditor.
     */
    imageDialog: function (editor) {
      var width = Math.min(1000, parseInt(screen.availWidth * 0.8));
      var height = Math.min(800, parseInt(screen.availHeight * 0.8));
      var url = CKEDITOR.elfinder.url('sendto=CKEDITOR.elfinder.imageSendto&type=image&ck_id=' + encodeURIComponent(editor.name));
      //editor.popup(url, width, height);
      
      
      
	var elfinderOpts = {
	  url : drupalSettings.elfinder.connectorUrl,
	  lang : drupalSettings.elfinder.langCode,
	  rememberLastDir : drupalSettings.elfinder.rememberLastDir,
	  closeOnEditorCallback : false,
	  customData : {token: drupalSettings.elfinder.token},
	  editorCallback: function() {

	  },
	  getFileCallback: function(file, arg2) {



	  }
	}
      
      var elfinder = $('<div id="#finder"></div>').elfinder(elfinderOpts);
      
      var dialog = $(elfinder).dialog({
	minWidth: 800,
	minHeight: 200,
	modal: true,
	autoOpen: false,
	title: 'elFinder'
      });
      
      elfinder.dialog('open');
      
      /*editor.openDialog('about2', function(arg1) {
	console.log(arg1);

	console.log(arg1.parts.contents.$);
      
	$('#finder', arg1.parts.contents.$).elfinder(elfinderOpts);
	
      });*/
      
    },

    /**
     * Returns elFinder url.
     */
    url: function (query) {
      var url = Drupal.url('elfinder');
      if (query) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + query;
      }
      return url;
    }

  };

})(jQuery, Drupal, CKEDITOR);

