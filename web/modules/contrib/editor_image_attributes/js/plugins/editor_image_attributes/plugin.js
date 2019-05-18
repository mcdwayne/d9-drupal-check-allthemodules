/**
 * @file
 * Drupal Image Caption plugin.
 *
 * This alters the existing CKEditor image2 widget plugin, which is already
 * altered by the Drupal Image plugin, to:
 * - allow for the data-caption and data-align attributes to be set
 * - mimic the upcasting behavior of the caption_filter filter.
 *
 * @ignore
 */

(function (CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add('editorimageattributes', {
    requires: 'drupalimage',

    beforeInit: function (editor) {
      // Disable default placeholder text that comes with CKEditor's image2
      // plugin: it has an inferior UX (it requires the user to manually delete
      // the place holder text).
      // editor.lang.image2.captionPlaceholder = '';

      // Drupal.t() will not work inside CKEditor plugins because CKEditor loads
      // the JavaScript file instead of Drupal. Pull translated strings from the
      // plugin settings that are translated server-side.
      // var placeholderText = editor.config.drupalImageCaption_captionPlaceholderText;

      // Override the image2 widget definition to handle the additional
      // data-align and data-caption attributes.
      editor.on('widgetDefinition', function (event) {
        var widgetDefinition = event.data;
        if (widgetDefinition.name !== 'image') {
          return;
        }

        // Only perform the downcasting/upcasting for to the enabled filters.
        // var captionFilterEnabled = editor.config.drupalImageCaption_captionFilterEnabled;
        // var alignFilterEnabled = editor.config.drupalImageCaption_alignFilterEnabled;

        // Override default features definitions for drupalimagecaption.
        CKEDITOR.tools.extend(widgetDefinition.features, {
          editor_id: {
            requiredContent: 'img[editor_id]'
          },
          editor_class: {
            requiredContent: 'img[editor_class]'
          },
          editor_extra: {
            requiredContent: 'img[editor_extra]'
          }
        }, true);
        // Extend requiredContent & allowedContent.
        // CKEDITOR.style is an immutable object: we cannot modify its
        // definition to extend requiredContent. Hence we get the definition,
        // modify it, and pass it to a new CKEDITOR.style instance.
        // var requiredContent = widgetDefinition.requiredContent.
        // getDefinition();.
        widgetDefinition.allowedContent.img.attributes['!editor_id'] = true;
        widgetDefinition.allowedContent.img.attributes['!editor_class'] = true;
        widgetDefinition.allowedContent.img.attributes['!editor_extra'] = true;

        // Override downcast(): ensure we *only* output <img>, but also ensure
        // we include the data-entity-type, data-entity-uuid, data-align and
        // data-caption attributes.
        var originalDowncast = widgetDefinition.downcast;
        widgetDefinition.downcast = function (element) {
          var img = findElementByName(element, 'img');
          originalDowncast.call(this, img);

          var attrs = img.attributes;

          if (this.data.editor_id) {
            attrs['editor-id'] = this.data.editor_id;
          }

          if (this.data.editor_class) {
            attrs['editor-class'] = this.data.editor_class;
          }

          if (this.data.editor_extra) {
            attrs['editor-extra'] = this.data.editor_extra;
          }

          // If img is wrapped with a link, we want to return that link.
          if (img.parent.name === 'a') {
            return img.parent;
          }
          else {
            return img;
          }
        };

        // We want to upcast <img> elements to a DOM structure required by the
        // image2 widget. Depending on a case it may be:
        //   - just an <img> tag (non-captioned, not-centered image),
        //   - <img> tag in a paragraph (non-captioned, centered image),
        //   - <figure> tag (captioned image).
        // We take the same attributes into account as downcast() does.
        var originalUpcast = widgetDefinition.upcast;
        widgetDefinition.upcast = function (element, data) {
          if (element.name !== 'img' || !element.attributes['data-entity-type'] || !element.attributes['data-entity-uuid']) {
            return;
          }
          // Don't initialize on pasted fake objects.
          else if (element.attributes['data-cke-realelement']) {
            return;
          }

          element = originalUpcast.call(this, element, data);
          var attrs = element.attributes;

          if (element.parent.name === 'a') {
            element = element.parent;
          }

          var retElement = element;

          data['editor_id'] = attrs['editor-id'];
          delete attrs['editor-id'];
          data['editor_class'] = attrs['editor-class'];
          delete attrs['editor-class'];
          data['editor_extra'] = attrs['editor-extra'];
          delete attrs['editor-extra'];
          // Return the upcasted element (<img>, <figure> or <p>).
          return retElement;
        };

        // Protected; keys of the widget data to be sent to the Drupal dialog.
        // Append to the values defined by the drupalimage plugin.
        // @see core/modules/ckeditor/js/plugins/drupalimage/plugin.js
        CKEDITOR.tools.extend(widgetDefinition._mapDataToDialog, {
          editor_id: 'editor_id',
          editor_class: 'editor_class',
          editor_extra: 'editor_extra'
        });

        // Override Drupal dialog save callback.
        var originalCreateDialogSaveCallback = widgetDefinition._createDialogSaveCallback;
        widgetDefinition._createDialogSaveCallback = function (editor, widget) {
          var saveCallback = originalCreateDialogSaveCallback.call(this, editor, widget);

          return function (dialogReturnValues) {
            // Ensure hasCaption is a boolean. image2 assumes it always works
            // with booleans; if this is not the case, then
            // CKEDITOR.plugins.image2.stateShifter() will incorrectly mark
            // widget.data.hasCaption as "changed" (e.g. when hasCaption === 0
            // instead of hasCaption === false). This causes image2's "state
            // shifter" to enter the wrong branch of the algorithm and blow up.
            dialogReturnValues.attributes.hasCaption = !!dialogReturnValues.attributes.hasCaption;

            var actualWidget = saveCallback(dialogReturnValues);

            // By default, the template of captioned widget has no
            // data-placeholder attribute. Note that it also must be done when
            // upcasting existing elements (see widgetDefinition.upcast).
            if (dialogReturnValues.attributes.hasCaption) {
              actualWidget.editables.caption.setAttribute('data-placeholder', placeholderText);

              // Some browsers will add a <br> tag to a newly created DOM
              // element with no content. Remove this <br> if it is the only
              // thing in the caption. Our placeholder support requires the
              // element be entirely empty. See filter-caption.css.
              var captionElement = actualWidget.editables.caption.$;
              if (captionElement.childNodes.length === 1 && captionElement.childNodes.item(0).nodeName === 'BR') {
                captionElement.removeChild(captionElement.childNodes.item(0));
              }
            }
          };
        };
      // Low priority to ensure drupalimage's event handler runs first.
      }, null, null, 20);
    }
  });

  /**
   * Finds an element by its name.
   *
   * Function will check first the passed element itself and then all its
   * children in DFS order.
   *
   * @param {CKEDITOR.htmlParser.element} element
   *   The element to search.
   * @param {string} name
   *   The element name to search for.
   *
   * @return {?CKEDITOR.htmlParser.element}
   *   The found element, or null.
   */
  function findElementByName(element, name) {
    if (element.name === name) {
      return element;
    }

    var found = null;
    element.forEach(function (el) {
      if (el.name === name) {
        found = el;
        // Stop here.
        return false;
      }
    }, CKEDITOR.NODE_ELEMENT);
    return found;
  }

})(CKEDITOR);
