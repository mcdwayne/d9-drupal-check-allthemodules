/**
 * jQuery to show on beautytips admin settings page
 */
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.beautytipsAdmin = {
    attach: function (context, settings) {
      if (!$('#edit-beautytips-always-add').prop('checked')) {
        // Disable input and hide its description.
        $('#edit-beautytips-added-selectors-array').prop('disabled', 'disabled');
        $('.form-item-beautytips-added-selectors-array').hide(0);
      }
      $('#edit-beautytips-always-add').bind('click', function () {
        if ($('#edit-beautytips-always-add').prop('checked')) {
          // Auto-alias unchecked; enable input.
          $('#edit-beautytips-added-selectors-array').removeAttr('disabled');
          $('.form-item-beautytips-added-selectors-array').slideDown('fast');
        }
        else {
          // Auto-alias checked; disable input.
          $('#edit-beautytips-added-selectors-array').prop('disabled', 'disabled');
          $('.form-item-beautytips-added-selectors-array').slideUp('fast');
        }
      });

      // Add the color picker to certain textfields
      $('#edit-bt-options-box-fill, #edit-bt-options-box-strokestyle, #edit-bt-options-box-shadowcolor, #edit-bt-options-css-color').ColorPicker({
        onSubmit: function (hsb, hex, rgb, el) {
          $(el).val('#' + hex);
          $(el).ColorPickerHide();
        },
        onBeforeShow: function () {
          value = this.value.replace("#", "");
          $(this).ColorPickerSetColor(value);
        }
      })
          .bind('keyup', function () {
            $(this).ColorPickerSetColor(this.value);
          });


      var popupText = "Sed justo nibh, ultrices ut gravida et, laoreet et elit. Nullam consequat lacus et dui dignissim venenatis. Curabitur quis urna eget mi interdum viverra quis eu enim. Ut sit amet nunc augue. Morbi ferm entum ultricies velit sed aliquam. Etiam dui tortor, auctor sed tempus ac, auctor sed sapien.";
      themeSettings = beautytipsGetThemeSettings();
      currentTheme = $("input[name='beautytips_default_style']:checked").val();
      $("#beauty-default-styles input").click(function () {
        currentTheme = $("input[name='beautytips_default_style']:checked").val();
      });

      $("#beautytips-popup-changes").click(function () {
        options = beautytipsSetupDefaultOptions(themeSettings[currentTheme]);
        // General options
        $("#beautytips-site-wide-popup").next('details').find('.details-wrapper').children('.form-item:not(.beautytips-css-styling)').each(function () {
          var name = $(this).find('input').attr('name');
          var optionName = name.replace("bt-options-box-", "");
          var newValue = $(this).find('input').val();
          if (optionName == 'shadow') {
            newValue = $(".beautytips-options-shadow input[name='bt-options-box-shadow']:checked").val();
            newValue = newValue == 'default' ? null : (newValue == 'shadow' ? true : false);
          }
          if (newValue || newValue === false) {
            if (optionName == 'cornerRadius') {
              newValue = Number(newValue);
            }
            options[optionName] = newValue;
          }
        });
        // css options
        $('.beautytips-css-styling .form-item').each(function () {
          var newValue = $(this).find('input').val();
          var name = $(this).find('input').attr('name');
          var optionName = name.replace("bt-options-css-", "");
          if (!options['cssStyles']) {
            options['cssStyles'] = new Object();
          }
          if (newValue || newValue === false) {
            options['cssStyles'][optionName] = newValue;
          }
        });
        $(this).bt(popupText, options);
      });
    }
  }

  function beautytipsSetupDefaultOptions(themeSettings) {
    var options = new Array();

    for (var key in themeSettings) {
      if (key == 'cssStyles') {
        options['cssStyles'] = new Array();
        for (var option in themeSettings['cssStyles']) {
          options['cssStyles'][option] = themeSettings['cssStyles'][option];
        }
      }
      else {
        options[key] = themeSettings[key];
      }
    }
    options['positions'] = 'right';
    options['trigger'] = ['dblclick', 'click'];

    return options;
  }

  function beautytipsGetThemeSettings() {
    themeSettings = drupalSettings.beautytips;
    return themeSettings;
  }
})(jQuery, Drupal, drupalSettings);
