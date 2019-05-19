(function ($) {
  'use strict';
  Drupal.behaviors.weatherstation = {
    attach: function (context, settings) {
      $(drupalSettings.weatherstation_settings.id).ready(
        $.ajax({
          url: '/weatherstation/get-weather.json',
          type: 'get',
          success: function (data) {
            if (typeof data['error'] !== 'undefined') {
              $('.spinner').hide();
              return;
            }
            let css_href = drupalSettings.weatherstation_settings.theme;
            $('<link rel="stylesheet" type="text/css" href="' + css_href + '" />')
              .appendTo('head');

            drupalSettings.weatherstation = {current: JSON.parse(data)};
            let icon = drupalSettings.weatherstation.current.weather[0].icon;
            let background_img = drupalSettings.weatherstation_settings.photos[icon];
            let slogan = drupalSettings.weatherstation_settings.slogans[icon];
            let temp_type = drupalSettings.weatherstation_settings.temp;
            let weather_element = drupalSettings.weatherstation_settings.id;

            $(window).resize(function () {
              addSizeClass(weather_element);
            });

            $('#weatherstation-slogan p').html(slogan);
            $('#weatherstation-temp')
              .html(tempConvert(drupalSettings.weatherstation.current.main.temp, temp_type));
            $('#weatherstation-loc')
              .html(drupalSettings.weatherstation.current.name);
            $('#weatherstation-info')
              .html(drupalSettings.weatherstation.current.weather['0'].description);
            $(weather_element)
              .css('background-image', 'url(' + background_img + ')');
            $('.spinner').fadeOut(1000);
          },
          failure: function (errMsg) {
            $('.spinner').hide();
          }
        })
      );
    }
  };

  $(drupalSettings.weatherstation_settings.id).ready(function () {
    addSizeClass(drupalSettings.weatherstation_settings.id);
  });

  /**
   * Convert temperature.
   *
   * @param {number} amount
   *   Temperature value.
   * @param {string} type
   *   Temp Type.
   * @return {*}
   *   Converted temperature.
   */
  function tempConvert(amount, type) {
    let convert_amount;
    switch (type) {
      case 'c':
        convert_amount = (amount - 273.15).toFixed(0) + '&#186C';
        break;
      case 'k':
        convert_amount = amount.toFixed(0) + ' K';
        break;
      case 'f':
        convert_amount = ((amount - 273.15) * 1.8000 + 32.00).toFixed(0) + '&#186F';
        break;
      case 'n':
      default:
        return false;
    }
    return convert_amount;
  }

  /**
   * Add size class.
   *
   * @param {object} weather_element
   *   Object weather container.
   */
  function addSizeClass(weather_element) {
    let parent_width = $(weather_element).parent().width();
    if ((parent_width) > 768) {
      $(weather_element).removeClass('small');
      $(weather_element).addClass('large');
    }
    else {
      $(weather_element).removeClass('large');
      $(weather_element).addClass('small');
    }
  }

})(jQuery);
