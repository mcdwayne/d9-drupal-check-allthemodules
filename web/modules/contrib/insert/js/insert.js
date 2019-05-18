(function($, Drupal, drupalSettings) {
  'use strict';

  /**
   * @type {Drupal.insert.FocusManager}
   */
  var focusManager;

  /**
   * @type {Drupal.insert.EditorManager|undefined}
   */
  var editorManager;

  /**
   * Behavior to add "Insert" buttons.
   */
  Drupal.behaviors.insert = {};
  Drupal.behaviors.insert.attach = function(context) {

    var editorInterface = undefined;

    $.each(Drupal.insert.editors.interfaces, function() {
      if (this.check()) {
        editorInterface = this;
        return false;
      }
    });

    focusManager = focusManager || new Drupal.insert.FocusManager(
      editorInterface
    );

    focusManager.addTextareas($('textarea:not([name$="[data][title]"])'));

    if (editorInterface) {
      editorManager = editorManager || new Drupal.insert.EditorManager(
        editorInterface
      );

      // Aggregate classes each time the behaviour is triggered as another Insert
      // type ("image", "file"), that has not been loaded yet, might have been
      // loaded now.
      editorManager.updateClassesToRetain(aggregateClassesToRetain());
    }

    // insert.js is loaded on page load.
    if (editorInterface) {
      $(editorInterface).on('instanceReady', function(e) {
        if (editorManager) {
          editorManager.addEditor(e.editor);
        }
        focusManager.addEditor(e.editor);
      });
    }

    // insert.js is loaded asynchronously.
    $.each(editorInterface.getInstances(), function(id, editor) {
      if (editorInterface.isReady(editor)) {
        if (editorManager) {
          editorManager.addEditor(editor);
        }
        focusManager.addEditor(editor);
      }
    });

    $('.insert', context).each(function() {
      var $insert = $(this);

      if (!$insert.data('insert')) {
        var inserter = new Drupal.insert.Inserter(this, focusManager, editorInterface);
        $insert.data('insert', inserter);

        focusManager.setDefaultTarget(determineDefaultTarget($insert).get(0));
      }

      var insertType = $insert.data('insert-type');

      if (insertType !== 'file' && insertType !== 'image') {
        return true;
      }

      // Handle default insert types; Custom (third-party) insert types are to
      // be handled by the modules supplying such custom types.

      if (!$insert.data('insert-handler')) {
        $insert.data('insert-handler',
          new Drupal.insert[insertType === 'image'
            ? 'ImageHandler'
            : 'FileHandler'
            ](inserter, drupalSettings.insert.widgets[insertType])
        )
      }

      $(inserter).off('.insert').on('insert.insert', function() {
        return $insert.data('insert-handler').buildContent();
      });
    });

  };

  /**
   * CKEditor removes all other classes when setting a style defined in
   * CKEditor. Since it is impossible to inject solid code into CKEditor, CSS
   * classes that should be retained are gathered for checking against those
   * actually applied to individual images.
   *
   * @return {Object}
   */
  function aggregateClassesToRetain() {
    var classesToRetain = {};

    $.each(drupalSettings.insert.classes, function(type, typeClasses) {
      classesToRetain[type] = [];

      var classesToRetainString = typeClasses.insertClass
        + ' ' + typeClasses.styleClass;

      $.each(classesToRetainString.split(' '), function() {
        classesToRetain[type].push(this.trim());
      });
    });

    return classesToRetain;
  }

  /**
   * Determines the default target objects shall be inserted in. The default
   * target is used when no text area was focused yet.
   *
   * @param {jQuery} $insert
   * @return {jQuery}
   */
  function determineDefaultTarget($insert) {
    var $commentBody = $insert
      .parents('.comment-form')
      .find('#edit-comment-body-wrapper')
      .find('textarea.text-full');

    if ($commentBody.length) {
      return $commentBody;
    }

    return $('#edit-body-wrapper').find('textarea.text-full');
  }

})(jQuery, Drupal, drupalSettings);
