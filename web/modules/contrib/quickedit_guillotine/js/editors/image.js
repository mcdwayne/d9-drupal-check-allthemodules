/**
 * @file
 * Drag+drop based in-place editor for images.
 */

(function ($, _, Drupal) {

  'use strict';

  Drupal.quickedit.editors.image = Drupal.quickedit.EditorView.extend(/** @lends Drupal.quickedit.editors.image# */{

    /**
     * @constructs
     *
     * @augments Drupal.quickedit.EditorView
     *
     * @param {object} options
     *   Options for the image editor.
     */
    initialize: function (options) {
      Drupal.quickedit.EditorView.prototype.initialize.call(this, options);
      // Set our original value to our current HTML (for reverting).
      this.model.set('originalValue', this.$el.html().trim());
      // $.val() callback function for copying input from our custom form to
      // the Quick Edit Field Form.
      this.model.set('currentValue', function (index, value) {
        var matches = $(this).attr('name').match(/(alt|title)]$/);
        if (matches) {
          var name = matches[1];
          var $toolgroup = $('#' + options.fieldModel.toolbarView.getMainWysiwygToolgroupId());
          var $input = $toolgroup.find('.quickedit-image-field-info input[name="' + name + '"]');
          if ($input.length) {
            return $input.val();
          }
        }
      });
    },

    /**
     * @inheritdoc
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The field model that holds the state.
     * @param {string} state
     *   The state to change to.
     * @param {object} options
     *   State options, if needed by the state change.
     */
    stateChange: function (fieldModel, state, options) {
      var from = fieldModel.previous('state');
      switch (state) {
        case 'inactive':
          if (from === 'highlighted') {
            this.teardownToolbar(fieldModel);
          }
          break;

        case 'candidate':
          if (from === 'candidate') {
            this.renderField();
            this.teardownToolbar(fieldModel);
          }
          if (from !== 'inactive') {
            this.$el.find('.quickedit-image-dropzone').remove();
            this.$el.removeClass('quickedit-image-element');
          }
          if (from === 'invalid') {
            this.removeValidationErrors();
          }

          break;

        case 'highlighted':
          if (from === 'candidate') {
            fieldModel.set('state', 'inactive');
          }
          break;

        case 'activating':
          // Defer updating the field model until the current state change has
          // propagated, to not trigger a nested state change event.
          _.defer(function () {
            fieldModel.set('state', 'active');
          });
          break;

        case 'active':
          var self = this;

          // Indicate that this element is being edited by Quick Edit Image.
          this.$el.addClass('quickedit-image-element');

          // Render our initial dropzone element. Once the user reverts changes
          // or saves a new image, this element is removed.
          var $dropzone = this.renderDropzone('upload', Drupal.t('Drop file here or click to upload'));

          $dropzone.on('dragenter', function (e) {
            $(this).addClass('hover');
          });
          $dropzone.on('dragleave', function (e) {
            $(this).removeClass('hover');
          });

          $dropzone.on('drop', function (e) {
            // Only respond when a file is dropped (could be another element).
            if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files.length) {
              $(this).removeClass('hover');
              self.uploadImage(e.originalEvent.dataTransfer.files[0]);
            }
          });

          $dropzone.on('click', function (e) {
            // Create an <input> element and append it to the DOM, and
            // trigger a click event. This is the easiest way to arbitrarily
            // open the browser's upload dialog (also works for IE 10,11).
            var element = document.createElement("input");
            element.style.display = "none";
            element.style.position = "absolute";
            element.type = "file";
            element.onchange = function() {
              if (this.files.length) {
                self.uploadImage(this.files[0]);
              }
            };
            $(document.body).append(element);
            element.click();
            $(element).remove();
          });

          // Prevent the browser's default behavior when dragging files onto
          // the document (usually opens them in the same tab).
          $dropzone.on('dragover dragenter dragleave drop click', function (e) {
            e.preventDefault();
            e.stopPropagation();
          });

          this.renderToolbar(fieldModel);
          break;

        case 'changed':
          break;

        case 'saving':
          if (from === 'invalid') {
            this.removeValidationErrors();
          }

          this.save(options);
          break;

        case 'saved':
          break;

        case 'invalid':
          this.showValidationErrors();
          break;
      }
    },

    teardownToolbar:function (fieldModel) {
      if (typeof fieldModel.editorView === 'undefined') {
        return;
      }

      // Unbind event handlers; remove toolbar element; delete toolbar view.
      fieldModel.toolbarView.remove();
      delete fieldModel.toolbarView;

      // Unbind event handlers; delete decoration view. Don't remove the element
      // because that would remove the field itself.
      fieldModel.decorationView.remove();
      delete fieldModel.decorationView;

      // Unbind event handlers; delete editor view. Don't remove the element
      // because that would remove the field itself.
      fieldModel.editorView.remove();
      delete fieldModel.editorView;
    },

    /**
     * Validates/uploads a given file.
     *
     * @param {File} file
     *   The file to upload.
     */
    uploadImage: function (file) {
      // Indicate loading by adding a special class to our icon.
      this.renderDropzone('upload loading', Drupal.t('Uploading <i>@file</i>…', {'@file': file.name}));

      // Build a valid URL for our endpoint.
      var fieldID = this.fieldModel.get('fieldID');
      var url = Drupal.quickedit.util.buildUrl(fieldID, Drupal.url('quickedit/image/upload/!entity_type/!id/!field_name/!langcode/!view_mode'));

      // Construct form data that our endpoint can consume.
      var data = new FormData();
      data.append('files[image]', file);

      // Set default height and width.
      var img_height = 793;
      var img_width = 1440;
      // Get element from field model.
      var element = this.fieldModel.editorView.$el;
      if (element[0]) {
        img_height = element[0].clientHeight;
        img_width = element[0].clientWidth;
      }

      // Construct a POST request to our endpoint.

      var self = this;
      this.ajax({
        type: 'POST',
        url: url,
        data: data,
        success: function (response) {
          var $el = $(self.fieldModel.get('el'));
          // Indicate that the field has changed - this enables the
          // "Save" button.
          self.fieldModel.set('state', 'changed');
          self.fieldModel.get('entity').set('inTempStore', true);
          self.removeValidationErrors();

          // Replace our html with the new image. If we replaced our entire
          // element with data.html, we would have to implement complicated logic
          // like what's in Drupal.quickedit.AppView.renderUpdatedField.
          var $content = $(response.html).closest('[data-quickedit-field-id]').children();
          $el.empty().append($content);
        },
        complete: function () {
          setTimeout(function () {
            self.addJs(img_width, img_height);
            self.guillotineToolbar();
          }, 1000);
        }
      });
    },

    renderField: function () {
      var fieldModel = this.fieldModel;
      var $fieldWrapper = $(fieldModel.get('el'));
      var $context = $fieldWrapper.parent();
      var fieldID = fieldModel.get('fieldID');
      var url = Drupal.quickedit.util.buildUrl(
        fieldID, Drupal.url(
          'quickedit/image/render/!entity_type/!id/!field_name/!langcode/!view_mode')
      );
      var renderField = function (html) {
        fieldModel.destroy();
        $fieldWrapper.replaceWith(html);
        Drupal.attachBehaviors($context.get(0));
      };

      this.ajax(
        {
          type: 'POST',
          url: url,
          success: function (response) {
            fieldModel.set('state', 'inactive', {reason: 'rerender'});
            renderField(response.html);
          }
        }
      );
    },

    addJs: function (width, height) {
      $("<link/>", {
        rel: "stylesheet",
        type: "text/css",
        href: window.location.origin + "/modules/custom/quickedit_guillotine/lib/jquery.guillotine.css"
      }).appendTo("head");

      jQuery.getScript(window.location.origin + '/modules/custom/quickedit_guillotine/lib/jquery.guillotine.js', function () {
        var data;
        var picture = $('.quickedit-editing picture img');
        picture.guillotine({
          width: width,
          height: height,
          onChange: function () {
            data = picture.guillotine('getData');
          }
        });

      });
    },

    guillotineToolbar: function () {

      var toolbarHtml;
      var $quickeditWrapper = $('.quickedit-toolbar-container');
      var $toolbarWrapper = $quickeditWrapper.find('.quickedit-toolbar-field');
      var picture = $('.quickedit-editing img');
      var svgFolder = window.location.origin + '/modules/custom/quickedit_guillotine/images';
      var data;

      toolbarHtml = '<div class="guillotine-toolbar-wrapper">\n' +
        '                  <div class="guillotine-toolbar-inside">\n' +
        '                    <div class="guillotine-toolbar-icon">\n' +
        '                      <button class="icon icon-zoom-out" type="button" title="' + Drupal.t('Zoom out') +'">\n' +
        '                        <span class="element-invisible">' + Drupal.t('Zoom out')+ '+</span>\n' +
        '                        <svg aria-hidden="true" class="svg-icon">\n' +
        '                          <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + svgFolder + '/sprite.min.svg#icon-zoom-out"></use>\n' +
        '                        </svg>\n' +
        '                      </button>\n' +
        '                    </div>\n' +
        '                    <div class="guillotine-toolbar-icon">\n' +
        '                      <button class="icon icon-fit-to-width" type="button" title="' + Drupal.t('Fit to width') +'">\n' +
        '                        <span class="element-invisible">' + Drupal.t('Fit to width') + '</span>\n' +
        '                        <svg aria-hidden="true" class="svg-icon">\n' +
        '                          <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + svgFolder + '/sprite.min.svg#icon-fit-to-width"></use>\n' +
        '                        </svg>\n' +
        '                      </button>\n' +
        '                    </div>\n' +
        '                    <div class="guillotine-toolbar-icon">\n' +
        '                      <button class="icon icon-zoom-in" type="button" title="' + Drupal.t('Zoom in') +'">\n' +
        '                        <span class="element-invisible">' + Drupal.t('Zoom in') + '</span>\n' +
        '                        <svg aria-hidden="true" class="svg-icon">\n' +
        '                          <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' + svgFolder + '/sprite.min.svg#icon-zoom-in"></use>\n' +
        '                        </svg>\n' +
        '                      </button>\n' +
        '                    </div>\n' +
        '                  </div>\n' +
        '                </div>\n' +
        ' <div class="quick-edit-message-wrapper">\n' +
        '   <div class="quick-edit-message-inside">\n' +
        '     <span class="alarm" aria-hidden="true">!</span><span class="text">' + Drupal.t('This file replaces the current image in the library, please make a backup of the previous image if you need it later.') +' </span>\n' +
        '   </div>\n' +
        ' </div>';

      $toolbarWrapper.append(toolbarHtml);

      var $zoomIn = $toolbarWrapper.find('.icon-zoom-in');
      var $zoomOut = $toolbarWrapper.find('.icon-zoom-out');
      var $fitToWidth = $toolbarWrapper.find('.icon-fit-to-width');

      $zoomIn.click(function (e) {
        e.preventDefault();
        data = picture.guillotine('getData');
        if (data['scale'] < 1.6) {
          picture.guillotine('zoomIn');
        }
      });

      $zoomOut.click(function (e) {
        e.preventDefault();
        picture.guillotine('zoomOut');
      });

      $fitToWidth.click(function (e) {
        e.preventDefault();
        picture.guillotine('fit');
      });

    },

    /**
     * Utility function to make an AJAX request to the server.
     *
     * In addition to formatting the correct request, this also handles error
     * codes and messages by displaying them visually inline with the image.
     *
     * Drupal.ajax is not called here as the Form API is unused by this
     * in-place editor, and our JSON requests/responses try to be
     * editor-agnostic. Ideally similar logic and routes could be used by
     * modules like CKEditor for drag+drop file uploads as well.
     *
     * @param {object} options
     *   Ajax options.
     * @param {string} options.type
     *   The type of request (i.e. GET, POST, PUT, DELETE, etc.)
     * @param {string} options.url
     *   The URL for the request.
     * @param {*} options.data
     *   The data to send to the server.
     * @param {function} options.success
     *   A callback function used when a request is successful, without errors.
     */
    ajax: function (options) {
      var defaultOptions = {
        context: this,
        dataType: 'json',
        cache: false,
        contentType: false,
        processData: false,
        error: function () {
          this.renderDropzone('error', Drupal.t('A server error has occurred.'));
        }
      };

      var ajaxOptions = $.extend(defaultOptions, options);
      var successCallback = ajaxOptions.success;

      // Handle the success callback.
      ajaxOptions.success = function (response) {
        if (response.main_error) {
          this.renderDropzone('error', response.main_error);
          if (response.errors.length) {
            this.model.set('validationErrors', response.errors);
          }
          this.showValidationErrors();
        }
        else {
          successCallback(response);
        }
      };

      $.ajax(ajaxOptions);
    },

    /**
     * Renders our toolbar form for editing metadata.
     *
     * @param {Drupal.quickedit.FieldModel} fieldModel
     *   The current Field Model.
     */
    renderToolbar: function (fieldModel) {
      var $toolgroup = $('#' + fieldModel.toolbarView.getMainWysiwygToolgroupId());
      var $toolbar = $toolgroup.find('.quickedit-image-field-info');
      if ($toolbar.length === 0) {
        // Perform an AJAX request for extra image info (alt/title).
        var fieldID = fieldModel.get('fieldID');
        var url = Drupal.quickedit.util.buildUrl(fieldID, Drupal.url('quickedit/image/info/!entity_type/!id/!field_name/!langcode/!view_mode'));
        var self = this;
        self.ajax({
          type: 'GET',
          url: url,
          success: function (response) {
            $toolbar = $(Drupal.theme.quickeditImageToolbar(response));
            $toolgroup.append($toolbar);
            $toolbar.on('keyup paste', function () {
              fieldModel.set('state', 'changed');
            });
            // Re-position the toolbar, which could have changed size.
            fieldModel.get('entity').toolbarView.position();
          }
        });
      }
    },

    /**
     * Renders our dropzone element.
     *
     * @param {string} state
     *   The current state of our editor. Only used for visual styling.
     * @param {string} text
     *   The text to display in the dropzone area.
     *
     * @return {jQuery}
     *   The rendered dropzone.
     */
    renderDropzone: function (state, text) {
      var $dropzone = this.$el.find('.quickedit-image-dropzone');
      // If the element already exists, modify its contents.
      if ($dropzone.length) {
        $dropzone
          .removeClass('upload error hover loading')
          .addClass('.quickedit-image-dropzone ' + state)
          .children('.quickedit-image-text')
          .html(text);
      }
      else {
        $dropzone = $(Drupal.theme('quickeditImageDropzone', {
          state: state,
          text: text
        }));
        this.$el.append($dropzone);
      }

      return $dropzone;
    },

    /**
     * @inheritdoc
     */
    revert: function () {
      this.$el.html(this.model.get('originalValue'));
    },

    /**
     * @inheritdoc
     */
    getQuickEditUISettings: function () {
      return {padding: false, unifiedToolbar: true, fullWidthToolbar: true, popup: false};
    },

    /**
     * @inheritdoc
     */
    showValidationErrors: function () {
      var errors = Drupal.theme('quickeditImageErrors', {
        errors: this.model.get('validationErrors')
      });
      $('#' + this.fieldModel.toolbarView.getMainWysiwygToolgroupId())
        .append(errors);
      this.getEditedElement()
        .addClass('quickedit-validation-error');
      // Re-position the toolbar, which could have changed size.
      this.fieldModel.get('entity').toolbarView.position();
    },

    /**
     * @inheritdoc
     */
    removeValidationErrors: function () {
      $('#' + this.fieldModel.toolbarView.getMainWysiwygToolgroupId())
        .find('.quickedit-image-errors').remove();
      this.getEditedElement()
        .removeClass('quickedit-validation-error');
    },

    /**
     * Saves the modified value in the in-place editor for this field.
     */
    save: function () {
      var fieldModel = this.fieldModel;
      var editorModel = this.model;
      var backstageId = 'quickedit_backstage-' + this.fieldModel.id.replace(/[\/\[\]\_\s]/g, '-');
      var picture = $('.quickedit-editing picture img');
      var data;

      if (typeof picture.guillotine != 'undefined') {
        data = picture.guillotine('getData');
      }

      function fillAndSubmitForm(value) {
        var $form = $('#' + backstageId).find('form');

        // Fill in the value in any <input> that isn't hidden or a submit
        // button.
        $form.find(':input[type!="hidden"][type!="submit"]:not(select)')
        // Don't mess with the node summary.
          .not('[name$="\\[summary\\]"]').val(value);

        var original_values = [];
        original_values['width'] = $form.find('input[name="field_image[0][width]"]').val();
        original_values['height'] = $form.find('input[name="field_image[0][height]"]').val();

        var pic_real_width;
        var pic_real_height;
        var height_grad;
        var width_grad;

        $("<img/>").attr('src', picture.attr("src")).load(function () {
          pic_real_width = this.width;   // Note: $(this).width() will not
          pic_real_height = this.height; // work for in memory images.
          if (data) {
            height_grad = original_values['height'] / pic_real_height;
            width_grad = original_values['width'] / pic_real_width;
            var height = height_grad * data['h'] / data['scale'];
            var width = width_grad * data['w'] / data['scale'];

            // Magic CROP formula.
            var $X = data['x'] * original_values['width'] / pic_real_width / data['scale'];
            var $Y = data['y'] * original_values['height'] / pic_real_height / data['scale'];

            $form.find('input[name="field_image[0][image_crop][crop_wrapper][image_widget_crop][crop_container][values][x]"]').val($X);
            $form.find('input[name="field_image[0][image_crop][crop_wrapper][image_widget_crop][crop_container][values][y]"]').val($Y);
            $form.find('input[name="field_image[0][image_crop][crop_wrapper][image_widget_crop][crop_container][values][width]"]').val(width);
            $form.find('input[name="field_image[0][image_crop][crop_wrapper][image_widget_crop][crop_container][values][height]"]').val(height);
            $form.find('input[name="field_image[0][image_crop][crop_wrapper][image_widget_crop][crop_container][values][crop_applied]"]').val('1');
          }

          // Submit the form.
          $form.find('.quickedit-form-submit').trigger('click.quickedit');
        });

      }

      var formOptions = {
        fieldID: this.fieldModel.get('fieldID'),
        $el: this.$el,
        nocssjs: true,
        coordinates: data,
        other_view_modes: fieldModel.findOtherViewModes(),
        // Reset an existing entry for this entity in the PrivateTempStore (if
        // any) when saving the field. Logically speaking, this should happen in
        // a separate request because this is an entity-level operation, not a
        // field-level operation. But that would require an additional request,
        // that might not even be necessary: it is only when a user saves a
        // first changed field for an entity that this needs to happen:
        // precisely now!
        reset: !this.fieldModel.get('entity').get('inTempStore')
      };

      var self = this;
      Drupal.quickedit.util.form.load(formOptions, function (form, ajax) {
        // Create a backstage area for storing forms that are hidden from view
        // (hence "backstage" — since the editing doesn't happen in the form, it
        // happens "directly" in the content, the form is only used for saving).
        var $backstage = $(Drupal.theme('quickeditBackstage', {id: backstageId})).appendTo('body');
        // Hidden forms are stuffed into the backstage container for this field.
        var $form = $(form).appendTo($backstage);
        // Disable the browser's HTML5 validation; we only care about server-
        // side validation. (Not disabling this will actually cause problems
        // because browsers don't like to set HTML5 validation errors on hidden
        // forms.)
        $form.prop('novalidate', true);
        var $submit = $form.find('.quickedit-form-submit');
        self.formSaveAjax = Drupal.quickedit.util.form.ajaxifySaving(formOptions, $submit);

        function removeHiddenForm() {
          Drupal.quickedit.util.form.unajaxifySaving(self.formSaveAjax);
          delete self.formSaveAjax;
          $backstage.remove();
        }

        // Successfully saved.
        self.formSaveAjax.commands.quickeditFieldFormSaved = function (ajax, response, status) {
          removeHiddenForm();
          // First, transition the state to 'saved'.
          fieldModel.set('state', 'saved');
          // Second, set the 'htmlForOtherViewModes' attribute, so that when
          // this field is rerendered, the change can be propagated to other
          // instances of this field, which may be displayed in different view
          // modes.
          fieldModel.set('htmlForOtherViewModes', response.other_view_modes);
          // Finally, set the 'html' attribute on the field model. This will
          // cause the field to be rerendered.
          // fieldModel.set('html', response.data);
          _.defer(
            function () {
              fieldModel.set('state', 'candidate');
            }
          );
        };

        // Unsuccessfully saved; validation errors.
        self.formSaveAjax.commands.quickeditFieldFormValidationErrors = function (ajax, response, status) {
          removeHiddenForm();
          editorModel.set('validationErrors', response.data);
          fieldModel.set('state', 'invalid');
        };

        // The quickeditFieldForm AJAX command is only called upon loading the
        // form for the first time, and when there are validation errors in the
        // form; Form API then marks which form items have errors. This is
        // useful for the form-based in-place editor, but pointless for any
        // other: the form itself won't be visible at all anyway! So, we just
        // ignore it.
        self.formSaveAjax.commands.quickeditFieldForm = function () {
        };

        fillAndSubmitForm(editorModel.get('currentValue'));
      });
    },

  });

})(jQuery, _, Drupal);
