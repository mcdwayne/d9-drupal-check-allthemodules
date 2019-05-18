/**
 * @file
 * This plugin has been developed on top of find/replace plugin developed by Frederico Knabben.
 */
(function ($) {
  'use strict';
  CKEDITOR.plugins.add('ckcs', {
    requires: 'dialog',
    // jscs:disable maximumLineLength
    lang: 'af,ar,az,bg,bn,bs,ca,cs,cy,da,de,de-ch,el,en,en-au,en-ca,en-gb,eo,es,es-mx,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,oc,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,tt,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%
    // jscs:enable maximumLineLength
    // icons: 'find,find-rtl,replace', // %REMOVE_LINE_CORE%
    hidpi: true, // %REMOVE_LINE_CORE%.
    init: function (editor) {
      var CkcsCommand = editor.addCommand('ckcs', new CKEDITOR.dialogCommand('ckcs'));
      CkcsCommand.canUndo = false;
      CkcsCommand.readOnly = 1;

      if (editor.ui.addButton) {
        editor.ui.addButton('ckcs', {
          label: 'Content Style Guide',
          command: 'ckcs',
          icon: this.path + 'icons/icon.png',
          toolbar: 'ckcs,10'
        });
      }

      CKEDITOR.dialog.add('ckcs', this.path + 'dialogs/ckcs.js');
    }
  });

  /**
   * Defines the style to be used to highlight results with the find dialog.
   *
   * @cfg
   * @member CKEDITOR.config
   */
  CKEDITOR.config.find_highlight = {element: 'span', styles: {'background-color': '#004', 'color': '#fff'}};
}(jQuery));
