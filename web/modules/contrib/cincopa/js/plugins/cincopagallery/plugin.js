/**
 * @file
 * Cincopa Gallery plugin.
 *
 * Use a Drupal-native dialog (that is in fact just an alterable Drupal form
 * like any other) instead of CKEditor's own dialogs.
 *
 * @see \Drupal\editor\Form\EditorImageDialog
 *
 * @ignore
 */
(function (jQuery, Drupal, CKEDITOR) {
  jQuery(document).ready(function(){
    if(getCookie('cincopa_help_close')) {
      jQuery(".cincopa_help_wrapper").hide();
    }

    jQuery("#icon_close").on("click", function(){
      jQuery(".cincopa_help_wrapper").slideUp();
      setCookie('cincopa_help_close','1');
    });
  });

  function setCookie(key, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (60 * 1 * 24 * 60 * 60 * 1000));
    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString() + '; path=/';
  }

  function getCookie(key) {
    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
    return keyValue ? keyValue[2] : null;
  }

  var insertContent;

  var cincopaSaveCallback = function(data) {
    var content = data.image_render;
    insertContent(content);
  };

  var cincopaDialogCallback = function(data) {
    return "https://www.cincopa.com/media-platform/my-galleries-getlist?disable_editor=true";
  }

  CKEDITOR.plugins.add('cincopagallery', {
      icons: 'cincopagallery',
      hidpi: true,
      beforeInit: function (editor) {
        editor.addCommand( 'cincopa_gallery', {
          canUndo: true,
          exec: function (editor, data) {
            var existingValues = {};
            //To Open Popup Dialog box....
            Drupal.ckeditor.openDialog(editor, Drupal.url('cincopa/dialog/gallery/' + editor.config.drupal.format), existingValues, cincopaSaveCallback, {
                                                    resizable: true,
                                                    width: 1000,
                                                    height: 500,
                                                  });
          }
        });

        editor.ui.addButton('Cincopagallery', {
          label: Drupal.t('Cincopa New Gallery'),
          // Note that we use the original image2 command!
          command: 'cincopa_gallery'
        });

        insertContent = function(html) {
          editor.insertHtml(html);
        }
      }
  });
})(jQuery, Drupal, CKEDITOR);