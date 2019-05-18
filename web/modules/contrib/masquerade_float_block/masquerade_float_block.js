/**
 * @file
 * Behaviors for Masquerade Float Block.
 *
 * This code initialize masquerade block with jQuery UI.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Helper functions.
   *
   * Compare library versions.
   *
   * @param version1 string
   *  version1
   * @param version2 string
   *   version2
   *
   * @return
   *    0 if two params are equal
   *    1 if the second is lower
   *   -1 if the second is higher
   *   TODO: Do we still need support old versions?
   */
  var versionCompare = function (version1, version2) {
    if (version1 == version2) {
      return 0;
    }
    var v1 = normalize(version1);
    var v2 = normalize(version2);
    var len = Math.max(v1.length, v2.length);
    for (var i = 0; i < len; i++) {
      v1[i] = v1[i] || 0;
      v2[i] = v2[i] || 0;
      if (v1[i] == v2[i]) {
        continue;
      }
      return v1[i] > v2[i] ? 1 : -1;
    }
    return 0;
  };

  function normalize(version) {
    return $.map(version.split('.'), function (value) {
      return parseInt(value, 10);
    });
  }

  Drupal.behaviors.masquerade_float_block = {
    attach: function (context) {
      $('body', context).once('masquerade-float-block').each(function () {
        var t = Drupal.t;
        var form = drupalSettings.masquerade_float_block.block.content;
        var $dialog = $('<div />').attr({
          title: drupalSettings.masquerade_float_block.block.subject,
          class: 'ui-corner-all'
          }).html(form);

        var switcher = $('<div />').attr({style: "position: absolute;top:25px;right:0;background-color:black;color:white;cursor:pointer;z-index:999;padding:.125em 0;font-family:'Source Sans Pro','Lucida Grande',Verdana,sans-serif;"});

        $(this).append($dialog);

        // 0 - closed.
        // 1 - opened.
        var dialog_state = $.cookie('mfb-dialog-state') || 1;
        // Elements position memory.
        var switcher_pos_top = $.cookie('mfb-switcher_pos_top') || 25;
        var switcher_pos_left = $.cookie('mfb-switcher_pos_left') || 0;
        var dialog_pos_top = $.cookie('mfb-dialog_pos_top') || 25;
        var dialog_pos_left = $.cookie('mfb-dialog_pos_left') || 0;

        switcher.css({top: switcher_pos_top + 'px', left: switcher_pos_left + 'px'});
        var dialogPosition = 'center';
        if (typeof $.ui.version !== 'undefined') {
          if (versionCompare('1.10', $.ui.version) === 1 || versionCompare('1.10', $.ui.version) === 0) {
            dialogPosition = [parseInt(dialog_pos_left), parseInt(dialog_pos_top)];
          }
          else {
            dialogPosition = {
              my: "left+" + dialog_pos_left + " top+" + dialog_pos_top,
              at: "left top",
              of: window
            };
          }
        }

        var dialogOptions = {
          autoOpen: dialog_state == 1,
          position: dialogPosition,
          resizable: false,
          dragStop: function (event, ui) {
            $.cookie('mfb-dialog_pos_top', ui.position.top, {path: '/'});
            $.cookie('mfb-dialog_pos_left', ui.position.left, {path: '/'});
          },
          modal: true,
          close: function (event, ui) {
            switcher.show();
            $.cookie('mfb-dialog-state', 0, {path: '/'});
          },
          open: function (event, ui) {
            $.cookie('mfb-dialog-state', 1, {path: '/'});
          },
          buttons: {
            "Hide": function () {
              $(this).dialog("close");
              switcher.show();
            }
          }
        };

        $dialog.dialog(dialogOptions);
        Drupal.behaviors.autocomplete.attach($dialog, drupalSettings);

        switcher.width('12em');

        switcher.draggable({
          stop: function (event, ui) {
            $.cookie('mfb-switcher_pos_top', ui.position.top, {path: '/'});
            $.cookie('mfb-switcher_pos_left', ui.position.left, {path: '/'});
          }
        });

        switcher.html(t('Show masquerade block'));
        switcher.prepend('<span style="cursor:move;letter-spacing:-.25em;padding:.25em .5em .25em .125em;">&#8942;&#8942;</span>');
        $(this).append(switcher);

        if (dialog_state == 1) {
          switcher.hide();
        }

        switcher.click(function () {
          $dialog.dialog('open');
          switcher.hide();
        });

      });
    }
  };

})(jQuery, Drupal, drupalSettings);
