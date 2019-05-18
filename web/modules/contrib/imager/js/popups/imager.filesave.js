/**
 * @file
 * Declare Imager module FileSave dialog - Drupal.imager.popups.filesaveC.
 */

/*
 * Note: Variables ending with capital C or M designate Classes and Modules.
 * They can be found in their own files using the following convention:
 *   i.e. Drupal.imager.coreM is in file imager/js/imager.core.js
 *        Drupal.imager.popups.baseC is in file imager/js/popups/imager.base.js
 * Variables starting with $ are only used for jQuery 'wrapped sets' of objects.
 */

/**
 * Wrap file in JQuery();.
 *
 * @param $
 */
(function ($) {
  'use strict';

  /**
   * Declare Filesave Dialog class.
   *
   * @param {object} spec
   *   Specifications for opening dialog, can also have ad-hoc properties
   *   not used by jQuery dialog but needed for other purposes.
   *
   * @return {popup}
   *   The filesave dialog.
   */
  Drupal.imager.popups.filesaveC = function filesaveC(spec) {
    var Popups = Drupal.imager.popups;
    var Core = Drupal.imager.core;
    var Viewer = Drupal.imager.viewer;

    var dspec = $.extend({
      name: 'Filesave',
      title: 'Save edited image to database',
      zIndex: 1015,
      cssId: 'imager-filesave',
      draggable: true,
      resizable: false,
      position: {
        left: '50px',
        top: '150px'
      }
    }, spec);
    // Initialize the popup.
    var popup = Drupal.imager.popups.baseC(dspec);

    popup.onButtonClick = function onButtonClick(buttonName) {
      switch (buttonName) {
        case 'imager-filesave-new-image':
          database(false);
          popup.dialogClose();
          break;

        case 'imager-filesave-overwrite':
          database(true);
          popup.dialogClose();
          break;

        case 'imager-filesave-download-image-image':
          download();
          popup.dialogClose();
          break;

        case 'imager-filesave-cancel':
          popup.dialogClose();
          break;
      }
    };

    var database = function database(overwrite) {
      Popups.$busy.show();
      var img = Drupal.imager.core.getImage($('input[name="resolution"]:checked').val(), false);
      Core.ajaxProcess(
        this,
        Drupal.imager.settings.actions.saveFile.url,
        {
          overwrite: overwrite,
          action: 'save-file',
          saveMode: popup.settings.saveMode,
          uri: Viewer.getImage().src,
          mid: Viewer.getImage().mid,
          imgBase64: img
        }, function (response) {
          var $row;
          if (response['file_new']) {
            Viewer.getImage().$container.after(response['file_new']);
            // @TODO The two unwrap are hardcoded to remove two extra divs.
            // Can this be done in PHP when it is rendered.
            // Maybe a Views tpl.php file.
            $row = Viewer.getImage().$container.next().find(Drupal.imager.settings.cssContainer);
            $row.unwrap().unwrap();
          }
          if (response['file_old']) {
            Viewer.getImage().$container.html(response['file_old']);

            /* @TODO - The following code is ugly,
               Views wraps a couple extra divs around the output.
               The following code removes those divs so just
               .views-row remains and everything below it remains.
                        $row =Viewer.getImage().$container.next().find(Drupal.imager.settings.cssContainer);
                        $row.unwrap().unwrap(); */

            $row = Viewer.getImage().$container.find(Drupal.imager.settings.cssContainer);
            // $row = Viewer.getImage().$container.find(Drupal.imager.settings.cssContainer).child();
            while (Viewer.getImage().$container[0] !== $row.parent()[0]) {
              $row.unwrap();
            }
            $row.unwrap();
          }
          Drupal.attachBehaviors($row);
        }
      );
      Popups.$busy.hide();
    };

    var download = function download() {
      Popups.$busy.show();
      var dataurl = Drupal.imager.core.getImage($('input[name="resolution"]:checked').val(), true);
      window.location.href = dataurl;
      window.location.download = 'downloadit.jpg';
      Popups.$busy.hide();

      /*    var img = document.createElement('img');
       img.src = dataurl;

       var a = document.createElement('a');
       a.setAttribute('download', 'YourFileName.jpeg');
       a.setAttribute('href', dataurl);
       //    a.appendChild(img);
       a.click();

       //    var w = window.open(img);
       //    w.document.title = 'Export Image';
       //    w.document.body.innerHTML = 'Left-click on the image to save it.';
       //    w.document.body.appendChild(a); */
    };

    /**
     * Send the image to the clipboard - isn't used currently.
     */
    function clipboard() {
      Popups.$busy.show();
      var img = Drupal.imager.core.getImage($('input[name="resolution"]:checked').val(), false);
      Core.ajaxProcess(this,
        Drupal.imager.settings.actions.clipboard.url,
        {
          action: 'clipboard',
          saveMode: popup.settings.saveMode,
          uri: Viewer.getImage().src,
          imgBase64: img
        }
      );
      Popups.$busy.hide();
      popup.dialogClose();
    }

    popup.dialogOnCreate = function dialogOnCreate() {
      popup.dialogOpen();
    };

    popup.dialogOnClose = function dialogOnClose() {
      switch (popup.settings.saveMode) {
        case 'save':
          $('#file-save').removeClass('checked');
          break;

        case 'download':
          $('#file-download').removeClass('checked');
          break;

        case 'clipboard':
          $('#file-clipboard').removeClass('checked');
          break;
      }
    };

    popup.dialogUpdate = function dialogUpdate() {
    };

    popup.dialogOnOpen = function dialogOnOpen() {
      Viewer.setEditMode(popup.settings.saveMode);
      var src = Drupal.imager.viewer.getImage().src;
      var filename = decodeURIComponent(src.substring(src.lastIndexOf('/') + 1));
      popup.$wrapper.find('#imager-filesave-filename').show().val(filename);
//    popup.$wrapper.find('#imager-filesave #imager-filesave-messages').hide();
      initTable();
      switch (popup.settings.saveMode) {
        case 'save':
          popup.$title.html('Save image to Database');
          $('#imager-filesave-new-image').show();
          $('#imager-filesave-overwrite').show();
          $('#imager-filesave-download-image').hide();
          break;

        case 'download':
          popup.$title.html('Download image');
          $('#imager-filesave-new-image').hide();
          $('#imager-filesave-overwrite').hide();
          $('#imager-filesave-download-image').show();
          break;

        case 'clipboard':
          popup.$title.html('Send image to Clipboard');
          break;
      }
    };

    var initTable = function initTable() {
      var status = Viewer.getStatus();
      var image = Viewer.getImage();
      var imageSize = Viewer.calculateDisplayedImage();
      $('#canvas-resolution').html(status.cw + 'x' + status.ch);
      $('#image-display-resolution').html(imageSize.width + 'x' + imageSize.height);
      $('#image-full-resolution').html(image.iw + 'x' + image.ih);
      $('#scale').html(parseInt(status.cscale * 100) / 100);
    };

    return popup;
  };

})(jQuery);
