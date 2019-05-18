(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.fiu_widget_preview = {
    attach: function (context) {

      var settings = {};

      addPreview(
        $('.fiu-fieldset-settings'),
        settings
      );

      /**
       * Add preview.
       * @param fieldset
       * @param settings
       */
      function addPreview(fieldset, settings) {
        getSettings(fieldset, settings);

        setSettings(fieldset, settings);

        /* Add event listener */
        eventListener(fieldset.selector, settings);
      }

      function eventListener(element_id, settings) {
        $(element_id + ' input').bind('input propertychange change', function (element) {
          getSettings($(element_id), settings);
          setSettings($(element_id), settings);
        });
      }

      /**
       * Get settings for preview.
       * @param fieldset
       * @param settings
       */
      function getSettings(fieldset, settings) {
        var inputs = fieldset.find('input');
        if (fieldset.selector == '.fiu-fieldset-settings')
        {
          settings.width = inputs[0].value;
          settings.height = inputs[1].value;
          settings.background = inputs[2].value;
          settings.label_size = inputs[3].value;
          settings.label_color = inputs[4].value;
          settings.label_color_hover = inputs[5].value;
          settings.imce_size = inputs[6].value;
          settings.imce_color = inputs[7].value;
          settings.imce_color_hover = inputs[8].value;
          settings.sources_links_size = inputs[9].value;
          settings.upload_color = inputs[10].value;
          settings.upload_color_hover = inputs[11].value;
          settings.remote_color = inputs[12].value;
          settings.remote_color_hover = inputs[13].value;
          settings.ref_color = inputs[14].value;
          settings.ref_color_hover = inputs[15].value;
        }
      }

      /**
       * Set settings for preview.
       * @param fieldset
       * @param settings
       */
      function setSettings(fieldset, settings) {
        var preview = fieldset.find('.fiu-widget-preview');

        // General settings.
        if('width' in settings) {
          preview.css('width', settings.width + 'px');
        }
        if('height' in settings) {
          $('.fiu-fieldset-settings').css('min-height', settings.height + 'px');
          preview.css('height', settings.height + 'px');
        }
        if('background' in settings) {
          preview.css('background-color', '#' + settings.background);
        }

        // Label settings.
        if('label_size' in settings) {
          preview.find('label').css('font-size', settings.label_size + 'px');
        }
        if('label_color' in settings) {
          preview.find('label').css('color', '#' + settings.label_color);
        }
        if('label_color_hover' in settings) {
          // Add hover events for label color.
          preview.find('label').mouseover(function() {
            $(this).css('color', '#' + settings.label_color_hover);
          }).mouseout(function() {
            $(this).css('color', '#' + settings.label_color);
          });
        }

        // 'Open File Browser' link settings.
        if('imce_size' in settings) {
          preview.find('.imce-filefield-link').css('font-size', settings.imce_size + 'px');
        }
        if('imce_color' in settings) {
          preview.find('.imce-filefield-link').css('color', '#' + settings.imce_color);
        }
        if('imce_color_hover' in settings) {
          // Add hover events for label color.
          preview.find('.imce-filefield-link').mouseover(function() {
            $(this).css('color', '#' + settings.imce_color_hover);
          }).mouseout(function() {
            $(this).css('color', '#' + settings.imce_color);
          });
        }

        // FileField Sources links size.
        if('sources_links_size' in settings) {
          preview.find('.filefield-sources-list').css('font-size', settings.sources_links_size + 'px');
        }

        // 'Upload' link colors.
        if('upload_color' in settings) {
          preview.find('.filefield-source-upload').css('color', '#' + settings.upload_color);
        }
        if('upload_color_hover' in settings) {
          // Add hover events for label color.
          preview.find('.filefield-source-upload').mouseover(function() {
            $(this).css('color', '#' + settings.upload_color_hover);
          }).mouseout(function() {
            $(this).css('color', '#' + settings.upload_color);
          });
        }

        // 'Remote URL' link colors.
        if('remote_color' in settings) {
          preview.find('.filefield-source-remote').css('color', '#' + settings.remote_color);
        }
        if('remote_color_hover' in settings) {
          // Add hover events for label color.
          preview.find('.filefield-source-remote').mouseover(function() {
            $(this).css('color', '#' + settings.remote_color_hover);
          }).mouseout(function() {
            $(this).css('color', '#' + settings.remote_color);
          });
        }

        // 'Reference existing' link colors.
        if('ref_color' in settings) {
          preview.find('.filefield-source-reference').css('color', '#' + settings.ref_color);
        }
        if('ref_color_hover' in settings) {
          // Add hover events for label color.
          preview.find('.filefield-source-reference').mouseover(function() {
            $(this).css('color', '#' + settings.ref_color_hover);
          }).mouseout(function() {
            $(this).css('color', '#' + settings.ref_color);
          });
        }

      }

    }
  }

})(jQuery, Drupal);
