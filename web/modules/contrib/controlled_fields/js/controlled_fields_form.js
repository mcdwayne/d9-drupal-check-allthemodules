(function($) {

  Drupal.behaviors.controlled_fields = {
    attach: function(context, settings) {
      var hideField = function($el) {
        $el.addClass('controlled-fields-hide');
        $el.slideUp('fast');
      }, showField = function($el) {
        $el.removeClass('controlled-fields-hide');
        $el.slideDown('fast');
      }, hideEmptyFieldGroups = function() {
        $('.controlled-fields-field-group').each(function() {
          var $controlledFields = $(this).find('.controlled-field');
          if ($controlledFields.length === $controlledFields.filter('.controlled-field.controlled-fields-hide').length) {
            $(this).addClass('controlled-fields-hide');
          }
          else {
            $(this).removeClass('controlled-fields-hide');
          }
        });
      }, updateField = function($el, doHide) {
        (function updateInnerField($el, doHide) {
          var doHide = (typeof doHide !== 'undefined') ? doHide : false,
            itemValues = [],
            matches = false,
            i,
            dependentsName = /controlled-master-(.*?)\s/g.exec($el.attr('class'));

          if (dependentsName !== null) {
            dependentsName = 'controlled-dependent-field-' + dependentsName[1];
          }

          // Early exit if we need to hide.
          if (doHide) {
            hideField($el);

            // Hide any children.
            $('.' + dependentsName).each(function() {
              updateInnerField($(this), true);
            });

            return false;
          }

          // Traverse inner fields.
          if ($el.find('select').length > 0) {
            itemValues.push($el.find('select').val());
          }
          else if ($el.find('input.form-checkbox').length > 0) {
            $el.find('input.form-checkbox').filter(':checked').each(function() {
              itemValues.push($(this).val());
            });
          }
          else {
            itemValues.push($el.find('input').filter(':checked').val());
          }

          for (i = 0; i < itemValues.length; i++) {
            if (itemValues[i] && dependentsName != null && $('.' + dependentsName + '.controlled-dependent-value-' + itemValues[i]).length > 0) {
              // Hide all dependents.
              // @todo this doesn't work with checkboxes nicely.
              $('.' + dependentsName).each(function() {
                updateInnerField($(this), true);
              });

              // Show the specific dependents.
              $('.' + dependentsName + '.controlled-dependent-value-' + itemValues[i]).each(function() {
                showField($(this));
                updateInnerField($(this));
              });

              matches = true;
            }
          }

          if (!matches) {
            // Hide any children.
            if (dependentsName != null) {
              $('.' + dependentsName).each(function() {
                updateInnerField($(this), true);
              });
            }
          }

          return false;

        })($el, doHide);

        return false;
      }

      // Dynamically add in class for required fields that are controlled.
      $('.controlled-required:not(.field--widget-field-collection-embed)').each(function() {
        if ($(this).find('span.fieldset-legend').length > 0) {
          $(this).find('span.fieldset-legend').addClass('form-required');
        }
        else {
          $(this).find('label').addClass('form-required');
        }
      });

      $('.controlled-master .form-radio, .controlled-master select, .controlled-master .form-checkbox')
        .change(function() {

        updateField($(this).parents('.controlled-master'));

        hideEmptyFieldGroups();
      });

      $('.controlled-master:not(.controlled-processed)').each(function() {
        if (!$(this).hasClass('controlled-dependent')) {
          updateField($(this));
        }

        $(this).addClass('controlled-processed')
      });

      hideEmptyFieldGroups();
    }
  }

})(jQuery);
