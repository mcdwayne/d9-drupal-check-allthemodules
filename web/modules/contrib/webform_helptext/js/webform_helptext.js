/**
 * @file
 * Contains webform_helptext.js.
 */

(function ($) {
  Drupal.behaviors.webformHelptext = {
    attach: function (context, settings) {
      var form_selector = settings.webform_helptext.form_selector;

      $('#' + form_selector, context).once('webformHelptext').each(function () {
        var formFields = settings.webform_helptext.form_fields;
        for (var field in formFields) {
          if (formFields[field].length > 0 || true) {
            // Build info icon.
            var infoIcon = $('<span></span>', {'class' : 'help-text-info-icon', 'id' : field}).text("i");
            var webform_component = $('*[data-info-icon="info-icon-' + field + '"]');
            infoIcon.attr('help-text', webform_component.data('help-text-' + field));

            // Adjust icon attachment depending on webform component type.
            if (webform_component.parents('.webform-datepicker').length > 0) {
              var datepicker_container = webform_component.closest('.webform-datepicker');
              datepicker_container.append(infoIcon);
            }
            else if (webform_component.is(':checkbox')) {
              var checkbox_container = webform_component.closest('.form-checkboxes');
              checkbox_container.append(infoIcon);
            }
            else if (webform_component.is(':radio')) {
              var radios_container = webform_component.closest('.form-radios');
              radios_container.append(infoIcon);
            }
            else {
              webform_component.parent().append(infoIcon);
            }
          }
        }

        // Show help text block on mouse enter.
        $('.help-text-info-icon', context).mouseenter(function (e) {
          var infoBlock = $('<span></span>', {'class' : 'help-text-info-block'}).text($(this).attr('help-text'));
          $(this).parent().append(infoBlock);
        });

        // Remove the help text block on mouse leave.
        $('.help-text-info-icon', context).mouseleave(function (e) {
          $('.help-text-info-block').remove();
        });
      });
    }
  }
}(jQuery));
