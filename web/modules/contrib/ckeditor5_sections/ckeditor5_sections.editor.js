/**
 * @file
 * The editor initialization.
 */

(function ($, Drupal) {

  'use strict';

  var editors = {};

  Drupal.editors.ckeditor5_sections = {
    attach: function attach(element, format) {
      init(element, format.editorSettings).then(editor => {
        editors[element.id] = editor;
        editor.model.document.on('change', () => {
          $(element).val(editor.getData());
          $(element).attr('data-editor-value-is-changed', 'true');
        });
      }).catch(error => {
        console.error(error.stack);
      });
    },
    detach: function (element, format, trigger) {
      // console.log('detach');
      if (trigger !== 'serialize') {
        editors[element.id].destroy();
      }
    },
    onChange: function (element, callback) {
      // console.log('change');
      // editors[element.id].on('change', debounce(callback, 500));
    }
  };

  /**
   * Returns a promise to initialize an editor instance.
   *
   * @param element
   *   The target input element.
   * @param editorSettings
   *   Editor settings.
   * @returns {editor}
   */
  function init(element, editorSettings) {
    $(element).hide();

    var editor = document.createElement('div');
    $(editor).insertAfter(element);
    editor.innerHTML = $(element).val();

    var currentCallback = null;

    var $updateButton = $('<button/>').attr('data-media-library-widget-update', $(element).attr('id'));
    var $updateValue = $('<input/>').attr('data-media-library-widget-value', $(element).attr('id'));

    $updateButton.insertAfter(element);
    $updateValue.insertAfter(element);

    $updateButton.hide();
    $updateValue.hide();

    $updateButton.mousedown(function (event) {
      event.preventDefault();
      if (currentCallback) {
        currentCallback($updateValue.val());
        currentCallback = null;
      }
    });

    $(window).on('editor:dialogsave', function (e, values) {
      if (values.attributes.fragment && values.attributes.fragment !== '_none') {
        values.attributes.href += '#' + values.attributes.fragment;
      }
      currentCallback(values.attributes);
    });

    window.ckeditor5_sections_builds = window.ckeditor5_sections_builds || {};

    return window.ckeditor5_sections_builds[editorSettings.editorBuild].create(editor, {
      masterTemplate: editorSettings.masterTemplate,
      templates: editorSettings.templates,
      templateAttributes: editorSettings.templateAttributes,
      templateSession: editorSettings.templateSession,

      drupalMediaSelector: { callback: (type, operation, callback) => {
        currentCallback = callback;
        var path = (operation === 'add') ? '/admin/content/media-widget-upload' : '/admin/content/media-widget';

        // Filter allowed media types.
        var typeFilter = '';
        if (typeof type != 'undefined') {
          var types = type.split(' ');
          types.forEach((item) => {
            typeFilter += '&media_library_allowed_types[' + item + ']=' + item;
          });
        }

        Drupal.ajax({
          url: path + '?media_library_widget_id=' + $(element).attr('id') + typeFilter + '&media_library_remaining=1&return_type=uuid',
          dialogType: 'modal',
          dialog: {
            dialogClass: 'media-library-widget-modal',
            heigh: '75%',
            width: '75%',
            title: 'Media library',
          }
        }).execute();
      } },
      drupalMediaRenderer: { callback: (uuid, display, callback) => {
        $.ajax('/sections/media-preview/' + uuid + '/' + display || 'default' ).done(callback);
      } },
      drupalLinkSelector: { callback: (existingValues = {},  callback ) => {
        currentCallback = callback;
        var dialogSettings = {
          title: existingValues.href ? Drupal.t('Edit link') : Drupal.t('Add link'),
          dialogClass: 'editor-link-dialog'
        };
        if (existingValues.href) {
          existingValues.href = existingValues.href.split('#')[0];
        }

        var classes = dialogSettings.dialogClass ? dialogSettings.dialogClass.split(' ') : [];
        dialogSettings.dialogClass = classes.join(' ');
        dialogSettings.autoResize = window.matchMedia('(min-width: 600px)').matches;
        dialogSettings.width = 'auto';

        var AjaxDialog = Drupal.ajax({
          dialog: dialogSettings,
          dialogType: 'modal',
          selector: '.ckeditor-dialog-loading-link',
          url:  Drupal.url('editor/dialog/link/ckeditor5_sections'),
          progress: { type: 'throbber' },
          submit: {
            editor_object: existingValues
          }
        });
        AjaxDialog.execute();
      } },
    });
  }
}(jQuery, Drupal));
