/* global imce:true */
(function ($, Drupal, imce) {
  'use strict';

  /**
   * @file
   * Defines Copy Link plugin for Imce.
   */

  /**
   * Init handler for Copy Link.
   */
  imce.bind('init', imce.copylinkInit = function () {
    if (imce.hasPermission('copylink')) {
      // Add toolbar button.
      imce.addTbb('copylink', {
        title: Drupal.t('Copy Link'),
        permission: 'copylink',
        shortcut: 'Ctrl+Alt+C',
        icon: 'copylink',
        handler: function() {
          var item = null;

          // Get the file/dir item.
          switch (imce.countSelection()) {
          case 0:
            item = imce.activeFolder;
            break;
          case 1:
            item = imce.selection[0];
            break;
          default:
            imce.setMessage(Drupal.t('Only one link may be copied at a time'));
            return;
          }
          if (!item) {
            imce.setMessage(Drupal.t('Nothing to copy'));
            return;
          }

          if (!imce.validatePermissions([item], 'copylink', 'copylink')) {
            imce.setMessage(Drupal.t('Copy link not authorized!'));
            return;
          }

          // Copy url to clipboard.
          var $buf = $('<input />').val(item.getUrl()).attr('style',
            'position:fixed;left:0;top:0;opacity:0;font-size:0px;' +
            'display:inline;pointer-events: none;');
          var $oldfocus = $(document.activeElement);

          if (!$oldfocus) {
            $('body').append($buf);
          }
          else {
            $oldfocus.after($buf);
          }

          $buf.focus().select();
          document.execCommand('copy');
          $buf.remove();

          if ($oldfocus) {
            $oldfocus.focus();
          }
        }
      });
    }
  });
})(jQuery, Drupal, imce);
