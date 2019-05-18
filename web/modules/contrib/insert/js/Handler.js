(function($, Drupal) {
  'use strict';

  /**
   * Builds content to be inserted.
   * @constructor
   *
   * @param {Drupal.insert.Inserter} inserter
   * @param {Object} [widgetSettings]
   * @param {HTMLElement} [wrapper]
   */
  Drupal.insert.Handler = Drupal.insert.Handler || (function() {

    /**
     * @constructor
     *
     * @param {Drupal.insert.Inserter} inserter
     * @param {Object} [widgetSettings]
     * @param {HTMLElement} [wrapper]
     */
    function Handler(inserter, widgetSettings, wrapper) {
      if (typeof inserter === 'undefined') {
        throw new Error('inserter needs to be specified.');
      }

      this._inserter = inserter;
      this._settings = widgetSettings || {};

      var self = this;
      var $wrapper = typeof wrapper === 'undefined'
        ? this._inserter.$container.parent() : $(wrapper);

      $.each(this._selectors, function() {
        $wrapper.find(this).on('input.insert', function() {
          self._update();
        });
      });
    }

    $.extend(Handler.prototype, {

      /**
       * @type {Object}
       */
      _selectors: {},

      /**
       * @type {Drupal.insert.Inserter}
       */
      _inserter: undefined,

      /**
       * @type {Object}
       */
      _settings: null,

      /**
       * The button overlay allows hover events over the button in disabled
       * state. The overlay is used only when the button is disabled and is used
       * to highlight invalid components when hovering the button.
       * @type {jQuery|undefined}
       */
      _$buttonOverlay: undefined,

      /**
       * @return {string}
       */
      buildContent: function() {
        var template = this._inserter.getTemplate();
        return this._attachValues(template);
      },

      /**
       * Attaches attributes and content according to data-insert-attach
       * definition.
       *
       * @param {string} template
       * @return {string}
       */
      _attachValues: function(template) {
        var self = this;
        var values = this._aggregateValues();
        var $tempContainer = $('<div>').html(template);

        $tempContainer.find('[data-insert-attach]').each(function() {
          self._setValues($(this), values);
        });

        return $tempContainer.html();
      },

      /**
       * Updates all registered textareas and editors with the current values
       * managed by this Handler instance.
       */
      _update: function() {
        var self = this;
        var syncId = this._inserter.$button.data('insert-id');

        if (typeof syncId === 'undefined') {
          return;
        }

        var values = this._aggregateValues();

        this._inserter.getFocusManager().getTextareas().each(function() {
          self._updateTextarea($(this), syncId, values);
        });

        $.each(this._inserter.getFocusManager().getEditors(), function() {
          self._updateEditor(this, syncId, values);
        });
      },

      /**
       * Updates a particular textarea with a set of values.
       *
       * @param {jQuery} $textarea
       * @param {string|int} syncId
       * @param {Object} values
       */
      _updateTextarea: function($textarea, syncId, values) {
        var self = this;
        var $dom = $('<div>').html($textarea.val());
        var $attachments = this._findByAttachmentId($dom, syncId);

        if ($attachments.length) {
          $attachments.each(function() {
            self._setValues($(this), values);
          });
          $textarea.val($dom.html());
        }
      },

      /**
       * Updates a particular editor with a set of values.
       *
       * @param {Drupal.insert.EditorInterface} editor
       * @param {string|int} syncId
       * @param {Object} values
       */
      _updateEditor: function(editor, syncId, values) {
        var self = this;
        var editorInterface = this._inserter.getEditorInterface();
        var $dom;

        try {
          $dom = editorInterface.getDom(editor);
        }
        catch(error) {
          // That editor has not been initialized yet.
          return;
        }

        this._findByAttachmentId($dom, syncId).each(function() {
          self._setValues($(this), values);
        });
      },

      /**
       * Finds attachments for a specific syncId.
       *
       * @param {jQuery} $dom
       * @param {string|int} syncId
       * @return {jQuery}
       */
      _findByAttachmentId: function($dom, syncId) {
        var $attachments = $();

        $dom.find('[data-insert-attach]').each(function() {
          if ($(this).data('insert-attach').id === syncId.toString()) {
            $attachments = $attachments.add(this);
          }
        });

        return $attachments;
      },

      /**
       * Sets attributes and/or content on a node according to its
       * data-insert-attach definition.
       *
       * @param {jQuery} $node
       * @param {Object} values
       */
      _setValues: function($node, values) {
        var attach = $node.data('insert-attach');

        if (attach.attributes) {
          $.each(attach.attributes, function(attributeName, keys) {
            $.each(keys, function() {
              if (values[this]) {
                if (values[this] === '') {
                  $node.removeAttr(attributeName);
                }
                else {
                  $node.attr(attributeName, values[this]);
                }
                return false;
              }
            });
          });
        }

        if (attach.content) {
          $.each(attach.content, function() {
            if (values[this]) {
              $node.text(values[this]);
              return false;
            }
          });
        }
      },

      /**
       * Returns all values gathered using this._selectors.
       *
       * @return {Object}
       */
      _aggregateValues: function() {
        var self = this;
        var values = {};
        var $fieldDataWrapper = this._inserter.$container.parent();

        $.each(this._selectors, function(key, selector) {
          var value = $(selector, $fieldDataWrapper).val();
          values[key] = value ? self._htmlEntities(value) : value;
        });

        return values;
      },

      /**
       * @param string
       * @return string
       */
      _htmlEntities: function(string) {
        return string
          .replace(/&/g, '&amp;')
          .replace(/"/g, '&quot;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;');
      },

      /**
       * @param {boolean} disable
       */
      _disable: function(disable) {
        if (!this._$buttonOverlay) {
          var $container = this._inserter.$container;
          this._$buttonOverlay = $container.find('.insert-button-overlay');
        }
        this._inserter.$button.prop('disabled', disable);
        this._$buttonOverlay[disable ? 'show' : 'hide']();
      }

    });

    return Handler;

  })();

})(jQuery, Drupal);
