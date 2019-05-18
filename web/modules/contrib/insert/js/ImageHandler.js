(function($, Drupal) {
  'use strict';

  var PARENT = Drupal.insert.FileHandler;

  /**
   * @type {Object}
   */
  var SELECTORS = {
    alt: 'input[name$="[alt]"], textarea[name$="[alt]"]',
    title: 'input[name$="[title]"], textarea[name$="[title]"]',
    description: 'input[name$="[description]"], textarea[name$="[description]"]'
  };

  /**
   * The names of the pseudo-styles that images may be inserted in without
   * having to specify the alternative text (if set to required).
   * @type {string[]}
   */
  var noAltTextInsertStyles = ['link', 'icon_link'];

  /**
   * Builds content to be inserted on image fields.
   * @constructor
   *
   * @param {Drupal.insert.Inserter} inserter
   * @param {Object} [widgetSettings]
   * @param {HTMLElement} [wrapper]
   */
  Drupal.insert.ImageHandler = Drupal.insert.ImageHandler || (function() {

    /**
     * @constructor
     *
     * @param {Drupal.insert.Inserter} inserter
     * @param {Object} [widgetSettings]
     * @param {HTMLElement} [wrapper]
     */
    function ImageHandler(inserter, widgetSettings, wrapper) {
      PARENT.prototype.constructor.apply(this, arguments);

      this._uuid = this._inserter.$container.data('uuid');

      var $rotator = this._inserter.$container.find('.insert-rotate');

      if ($rotator.length) {
        this._rotator = new Drupal.insert.Rotator(
          $rotator.get(0),
          this._inserter.$container.find('.insert-templates').get(0),
          this._inserter.getFocusManager(),
          this._inserter.getEditorInterface()
        );
      }

      this._initAltField();
      this._initAlign();

      this._disable(!this._checkAltField());
    }

    $.extend(ImageHandler.prototype, PARENT.prototype, {
      constructor: ImageHandler,

      /**
       * @type {Object}
       */
      _selectors: SELECTORS,

      /**
       * @type {string}
       */
      _uuid: undefined,

      /**
       * @type {Drupal.insert.Rotator|undefined}
       */
      _rotator: undefined,

      /**
       * The alternative text field corresponding to the image, if any.
       * @type {jQuery}
       */
      _$altField: undefined,

      /**
       * @type {jQuery}
       */
      _$align: undefined,

      /**
       * @inheritDoc
       *
       * @param {string} content
       * @return {HTMLElement|undefined}
       *
       * @triggers insertIntoActive
       */
      _insertIntoActive: function(content) {
        var activeElement = PARENT.prototype._insertIntoActive.call(this, content);

        if (activeElement) {
          this._contentWarning(activeElement, content);
        }

        return activeElement;
      },

      /**
       * Warns users when attempting to insert an image into an unsupported
       * field.
       *
       * This function is only a 90% use-case, as it does not support when the
       * filter tips are hidden, themed, or when only one format is available.
       * However, it should fail silently in these situations.
       *
       * @param {HTMLElement} editorElement
       * @param {string} content
       */
      _contentWarning: function(editorElement, content) {
        if (!content.match(/<img /)) {
          return;
        }

        var $wrapper = $(editorElement).parents('div.text-format-wrapper:first');
        if (!$wrapper.length) {
          return;
        }

        $wrapper.find('.filter-guidelines-item:visible li').each(function(index, element) {
          var expression = new RegExp(Drupal.t('Allowed HTML tags'));
          if (expression.exec(element.textContent) && !element.textContent.match(/<img( |>)/)) {
            alert(Drupal.t("The selected text format will not allow it to display images. The text format will need to be changed for this image to display properly when saved."));
          }
        });
      },

      /**
       * @inheritDoc
       *
       * @param {string} template
       * @return {string}
       */
      _attachValues: function(template) {
        template = PARENT.prototype._attachValues.call(this, template);
        return this._updateImageDimensions(template);
      },

      /**
       * Checks for a maximum dimension and scales down the width if necessary.
       *
       * @param {string} template
       * @return {string}
       *   Updated template.
       */
      _updateImageDimensions: function(template) {
        var widthMatches = template.match(/width[ ]*=[ ]*"(\d*)"/i);
        var heightMatches = template.match(/height[ ]*=[ ]*"(\d*)"/i);
        if (this._settings.maxWidth && widthMatches && parseInt(widthMatches[1]) > this._settings.maxWidth) {
          var insertRatio = this._settings.maxWidth / widthMatches[1];
          var width = this._settings.maxWidth;
          template = template.replace(/width[ ]*=[ ]*"?(\d*)"?/i, 'width="' + width + '"');

          if (heightMatches) {
            var height = Math.round(heightMatches[1] * insertRatio);
            template = template.replace(/height[ ]*=[ ]*"?(\d*)"?/i, 'height="' + height + '"');
          }
        }
        return template;
      },

      /**
       * Initializes the alternative text input element, if any.
       */
      _initAltField: function() {
        this._$altField = this._inserter.$container
          .parent()
          .find(this._selectors['alt']);

        // If no alt field is found, look for a description field as the
        // ImageHandler may be used to insert an image per file field.
        if (!this._$altField.length) {
          this._$altField = this._inserter.$container
            .parent()
            .find(this._selectors['description']);
        }

        if (!this._$altField.length) {
          return;
        }

        var self = this;

        this._$altField.on('input.insert_image_handler', function() {
          self._disable(!self._checkAltField());
        });

        this._inserter.$insertStyle.on('change.insert_image_handler', function() {
          self._disable(!self._checkAltField());
        });
      },

      /**
       * Checks whether the alternative text configuration, its input and the
       * selected style allows the image to get inserted. For example, if the
       * alternative text is required, it may not be empty to allow inserting an
       * image, as long as the image shall not be inserted in the form of a
       * plain text link.
       *
       * @return {boolean}
       *   TRUE if alternative text configuration/input is valid, FALSE if not.
       */
      _checkAltField: function() {
        return !this._$altField.length
          || !this._$altField.prop('required')
          || this._$altField.prop('required') && this._$altField.val().trim() !== ''
          || $.inArray(this._inserter.$insertStyle.val(), noAltTextInsertStyles) !== -1
      },

      /**
       * Initializes alignment setting, if available.
       */
      _initAlign: function() {
        var self = this;
        var editorInterface = this._inserter.getEditorInterface();

        this._$align = this._inserter.$container.find('.insert-align');

        this._$align.add(this._inserter.$button)
          .on('click.insert_image_handler', function() {
            var value = self._$align.find(':checked').val();

            self._inserter.getFocusManager().getTextareas().each(function() {
              var $textarea = $(this);
              var $dom = $('<div>').html($textarea.val());
              var $instances = self._findByUUID($dom, self._uuid);

              if ($instances.length) {
                $instances.attr('data-align', value);
                $textarea.val($dom.html());
              }
            });

            $.each(self._inserter.getFocusManager().getEditors(), function() {
              editorInterface.setAlign(this, self._uuid, value);
            });
          });

        var value = this._getAlign();
        this._$align.find('[value="' + value + '"]').prop('checked', true);
      },

      /**
       * Finds nodes by UUID.
       *
       * @param {jQuery} $dom
       * @param {string} uuid
       * @return {jQuery}
       */
      _findByUUID: function($dom, uuid) {
        var regExp = new RegExp(uuid + '$');
        var $instances = $();

        $dom.find('[data-entity-uuid]').each(function() {
          var uuid = $(this).data('entity-uuid');
          if (typeof uuid !== 'undefined' && regExp.test(uuid)) {
            $instances = $instances.add(this);
          }
        });

        return $instances;
      },

      /**
       * Returns current alignment simply using the first image instance.
       * (If there are different alignments for an image's instances, alignment
       * messed up somehow anyway.)
       *
       * @return {string}
       */
      _getAlign: function() {
        var self = this;
        var value;

        this._inserter.getFocusManager().getTextareas().each(function() {
          var $dom = $('<div>').html($(this).val());
          self._findByUUID($dom, self._uuid).each(function() {
            value = $(this).attr('data-align');
            return false;
          });
          return !value;
        });

        if (value) {
          return value;
        }

        var editorInterface = this._inserter.getEditorInterface();

        $.each(this._inserter.getFocusManager().getEditors(), function() {
          value = editorInterface.getAlign(this, self._uuid);
          return false;
        });

        return value ? value : 'none';
      },

      /**
       * @inheritDoc
       */
      _setValues: function($node, values) {
        PARENT.prototype._setValues.apply(this, arguments);

        var attach = $node.data('insert-attach');

        if (attach.attributes['data-caption']) {
          var text = '';
          $.each(attach.attributes['data-caption'], function() {
            if (typeof values[this] !== 'undefined' && values[this] !== '') {
              text = values[this];
              return false;
            }
          });
          var editorInterface = this._inserter.getEditorInterface();

          $.each(this._inserter.getFocusManager().getEditors(), function() {
            editorInterface.setCaption(this, attach.id, text);
          });
        }
      },

      /**
       * @param {boolean} disable
       */
      _disable: function(disable) {
        if (!this._$buttonOverlay) {
          var self = this;
          var $container = this._inserter.$container;

          this._$buttonOverlay = $container.find('.insert-button-overlay')
            .on('mouseover.insert', function() {
              self._$altField.addClass('insert-required');
            })
            .on('mouseout.insert', function() {
              self._$altField.removeClass('insert-required');
            });
        }

        PARENT.prototype._disable.apply(this, arguments);
      }

    });

    return ImageHandler;

  })();

})(jQuery, Drupal);
