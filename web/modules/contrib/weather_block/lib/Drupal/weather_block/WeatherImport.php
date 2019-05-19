<?php

namespace Drupal\weather_block;

class WeatherImport {

  /**
   * Converts fahrenheit to celcius.
   */
  public static function fahrenheitToCelsius($fahrenheit) {
    if ($fahrenheit == 'N/A') {
      return t('Not available');
    }
    $celcius = round(((int)$fahrenheit - 32) / 1.8);
    return $celcius;
  }

  /**
   * Converts celcius to fahrenheit.
   */
  public static function celsiusToFahrenheit($celcius) {
    if ($celcius == 'N/A') {
      return t('Not available');
    }

    $fahrenheit = round(((int)$celcius + 32) * 1.8);

    return $fahrenheit;
  }
}
