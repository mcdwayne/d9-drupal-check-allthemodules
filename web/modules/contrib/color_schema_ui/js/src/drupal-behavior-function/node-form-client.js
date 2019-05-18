import Picker from 'vanilla-picker/dist/vanilla-picker.js';
const axios = require('axios');
const invert = require('invert-color');
import AWN from "awesome-notifications";


(async ($, drupalSettings) => {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.toggler = {
    attach: async () => {
      const handleDisabledToggle = () => document.querySelector('.color-schema-ui-toggle.disabled').onclick = (event) => {
        event.preventDefault();
        document.body.innerHTML = document.body.innerHTML + drupalSettings.color_schema_ui.html_template;

        axios.post(drupalSettings.color_schema_ui.get_initial_colors + '?' + getRandomId())
          .then(function(response) {
            return response.data;
          }).then(function(data) {
          let initialColors = Object.entries(JSON.parse(JSON.stringify(data)));

          initialColors.forEach(function(initialColor) {
            jQuery('#color-schema-ui button.' + initialColor[0].replace(/-/g, '_')).css('background-color', initialColor[1]);
            jQuery('#color-schema-ui button.' + initialColor[0].replace(/-/g, '_')).css('color', invert(initialColor[1].match(/\d+/g).map(Number)));
          });

          handleColorPicking(initialColors);
        });

        document.querySelector('.color-schema-ui-toggle.disabled').classList.add('enabled');
        document.querySelector('.color-schema-ui-toggle.disabled').classList.remove('disabled');

        handleEnabledToggle();
      };
      handleDisabledToggle();

      const handleEnabledToggle = () => document.querySelector('.color-schema-ui-toggle.enabled').onclick = (event) => {
        event.preventDefault();

        document.querySelector('.color-schema-ui-toggle.enabled').classList.add('disabled');
        document.querySelector('.color-schema-ui-toggle.enabled').classList.remove('enabled');

        document.querySelector('#color-schema-ui').remove();

        handleDisabledToggle();
      };

      const handleColorPicking = (initialColors) => {
        let colorData = {},
          options = {
            position: 'top-right',
          },
          notifier = new AWN(options);

        for (let colorName of drupalSettings.color_schema_ui.color_machine_names) {

          let parent = document.querySelector('.' + colorName),
              picker = new Picker({
                parent: parent,
                color: initialColors.filter((initialColor) => {
                  if (typeof initialColor !== 'undefined' && initialColor[0].replace(/-/g, '_') === colorName) {
                    return true;
                  }
                }).map((initialColor) => {
                  return initialColor[1];
                }).reduce((initialColor) => {
                  return initialColor;
                })
              });

          picker.onDone = async (color) => {
            jQuery('#color-schema-ui button').prop('disabled', true);

            jQuery('#color-schema-ui button.' + colorName).css('background-color', color.rgbString);
            jQuery('#color-schema-ui button.' + colorName).css('color', invert(color.rgba));

            colorData[colorName] = color.rgbString;

            const css = await axios.post(drupalSettings.color_schema_ui.get_compiled_scss + '?' + getRandomId(), JSON.stringify(colorData))
              .then(function(response) {
                return response.data;
              }).then(function(data) {
                return data;
              });

            let inlineStyle = document.createElement('style');
            inlineStyle.type = 'text/css';
            inlineStyle.innerHTML = css;
            document.getElementsByTagName('head')[0].appendChild(inlineStyle);

            jQuery('#color-schema-ui button').prop('disabled', false);
          };
        }

        document.querySelector('#color-schema-ui button.save').onclick = () => {
          jQuery('#color-schema-ui button').prop('disabled', true);

          axios.post(drupalSettings.color_schema_ui.compile_scss_to_filesystem + '?' + getRandomId(), JSON.stringify(colorData))
            .then(function(response) {
              return response.data;
            }).then(function(data) {
            jQuery('#color-schema-ui button').prop('disabled', false);
            notifier.success(Drupal.t('Styles saved. They will be visible for guests after a cache flush.'));

            return data;
          });
        };
      }
    }
  };

  function getRandomId() {
    let text = "",
      possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for (let i = 0; i < 5; i++)
      text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
  }

})(jQuery, drupalSettings);
