(function($, Drupal) {
  'use strict';

  /**
   * @constructor
   */
  Drupal.insert.editors.CKEditor = Drupal.insert.editors.CKEditor || (function() {

    /**
     * @constructor
     */
    function CKEditor() {
      var self = this;

      if (this.check()) {
        CKEDITOR.on('instanceReady', function(e) {
          $(self).trigger({
            type: 'instanceReady',
            editor: e.editor
          });
        });
      }
    }

    $.extend(CKEditor.prototype, {
      constructor: CKEditor,

      /**
       * @inheritDoc
       */
      editorConstructor: CKEDITOR.editor,

      /**
       * @inheritDoc
       */
      check: function() {
        return typeof CKEDITOR !== 'undefined';
      },

      /**
       * @inheritDoc
       */
      getId: function(editor) {
        return editor.id;
      },

      /**
       * @inheritDoc
       */
      isReady: function(editor) {
        return editor.status === 'ready';
      },

      /**
       * @inheritDoc
       */
      getInstances: function() {
        return CKEDITOR.instances;
      },

      /**
       * @inheritDoc
       */
      getCurrentInstance: function() {
        return CKEDITOR.currentInstance;
      },

      /**
       * @inheritDoc
       */
      getElement: function(editor) {
        return editor.element ? editor.element.$ : undefined;
      },

      /**
       * @inheritDoc
       */
      getDom: function(editor) {
        return $(editor.document.$).find('body');
      },

      /**
       * @inheritDoc
       */
      getData: function(editor) {
        return editor.getData();
      },

      /**
       * @inheritDoc
       */
      setCaption: function(editor, syncId, text) {
        if (!editor.widgets) {
          // Since captions are managed by widgets, no caption to update is
          // present if there are no widgets.
          return;
        }

        $.each(editor.widgets.instances, function() {
          var $element = $(this.element.$);
          var attach = $element
            .find('[data-insert-attach]')
            .addBack('[data-insert-attach]')
            .data('insert-attach');

          if (!attach || syncId !== attach.id) {
            return true;
          }

          // Since setData will trigger events, avoid calling if there is no
          // reason to.
          if (text === '' && this.data.hasCaption) {
            this.setData('hasCaption', false);
          }
          else if (text !== '' && !this.data.hasCaption) {
            this.setData('hasCaption', true);
          }

          if (text !== '') {
            // Text will not be set when caption is just being added by setting
            // hasCaption to true, because setData is running asynchronously.
            // CKEDITOR.plugins.widget.setData does not support providing a
            // callback like CKEDITOR.editor.setData and
            // CKEDITOR.plugins.widget's data event is triggered before changes
            // are applied.
            $element.find('[data-caption]').attr('data-caption', text);
            $element.closest('.caption-img').find('figcaption').text(text);
          }
        });
      },

      /**
       * @inheritDoc
       */
      getAlign: function(editor, uuid) {
        var align = undefined;

        $.each(this._filterInstances(editor, uuid), function() {
          align = this.data.align;
          return false;
        });

        return align;
      },

      /**
       * @inheritDoc
       */
      setAlign: function(editor, uuid, value) {
        $.each(this._filterInstances(editor, uuid), function() {
          this.setData('align', value);
        });
      },

      /**
       * @param {CKEDITOR.editor} editor
       * @param {string} uuid
       * @return {CKEDITOR.plugins.widget[]}
       */
      _filterInstances: function(editor, uuid) {
        var instances = [];
        var regExp = new RegExp(uuid + '$');

        $.each(editor.widgets.instances, function() {
          var instanceUUID = this.data['data-entity-uuid'];
          if (instanceUUID && instanceUUID.match(regExp)) {
            instances.push(this);
          }
        });

        return instances;
      }

    });

    return CKEditor;

  })();

  Drupal.insert.editors.interfaces.CKEditor = new Drupal.insert.editors.CKEditor();

})(jQuery, Drupal);
