/* global imce:true */
(function ($, Drupal, imce) {
  'use strict';

  /**
   * @file
   * Defines Rename plugin for Imce.
   */

  /**
   * Init handler for Rename.
   */
  imce.bind('init', imce.renameInit = function () {
    var check_perm = imce.hasPermission('rename_files') || imce.hasPermission('rename_folders');

    if (check_perm) {
      // Add toolbar button.
      imce.addTbb('rename', {
        title: Drupal.t('Rename'),
        permission: 'rename_files|rename_folders',
        content: imce.createRenameForm(),
        shortcut: 'Ctrl+Alt+W',
        icon: 'file-text'
      });

      // Set old name in field New name.
      $('body').bind('DOMSubtreeModified', function () {

        if (imce.getSelection().length !== 0) {
          var items = imce.getSelection();
          // Get first selected item.
          var name = items[0].name;

          if (items[0].type === 'file') {
            name = name.substr(0, name.lastIndexOf('.'));
          }

          $('input[name=name]').val(name);
        }

      });
    }
  });

  /**
   * Creates rename form.
   *
   * @return {*}
   *    Return object form.
   */
  imce.createRenameForm = function () {
    var form = imce.renameForm;

    if (!form) {
      form = imce.renameForm = imce.createEl('<form class="imce-rename-form">' +
          '<div class="imce-form-item">' +
          '<label>' + Drupal.t('New name') + '</label>' +
          '<input type="text" name="name" size="30" maxlength="50" />' +
          '</div>' +
          '<div class="imce-form-actions">' +
          '<button type="submit" name="op" class="imce-rename-button">' + Drupal.t('Rename') + '</button>' +
          '</div>' +
          '</form>');
      form.onsubmit = imce.eRenameSubmit;

      var els = form.elements;
      els.name.placeholder = Drupal.t('Enter new name');
    }

    return form;
  };

  /**
   * Submit event for rename form.
   *
   * @return {boolean}
   *    Return false.
   */
  imce.eRenameSubmit = function () {
    var data;
    var els = this.elements;
    var new_name = els.name.value;
    var items = imce.getSelection();

    if (imce.validateRename(items, new_name)) {
      data = {new_name: new_name};
      imce.ajaxItems('rename', items, {data: data});
      imce.getTbb('rename').closePopup();
    }
    return false;

  };

  /**
   * Validates item renaming.
   *
   * @param {array} items
   *    All selected file.
   * @param {string} new_name
   *    The new name image.
   * @return {boolean}
   *    Return true or false.
   */
  imce.validateRename = function (items, new_name) {
    if (!imce.validatePermissions(items, 'rename_files', 'rename_folders')) {
      imce.getTbb('rename').closePopup();
      imce.setMessage(Drupal.t('You do not have permission to rename!'));
    }
    return new_name !== '' && imce.validatePermissions(items, 'rename_files', 'rename_folders');
  };

})(jQuery, Drupal, imce);
