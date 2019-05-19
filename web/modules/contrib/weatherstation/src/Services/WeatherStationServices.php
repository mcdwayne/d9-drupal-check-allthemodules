<?php

namespace Drupal\weatherstation\Services;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class WeatherStationServices.
 *
 * @package Drupal\weatherstation\Services
 */
class WeatherStationServices {

  use StringTranslationTrait;

  /**
   * Get array with weather icon and desc from Openweather.org .
   *
   * @return array
   *   Array with codes and short desc.
   */
  public function getIcons() {
    return array(
      '01d' => $this->t('Clear sky - day'),
      '01n' => $this->t('Clear sky - night'),
      '02d' => $this->t('Few clouds - day'),
      '02n' => $this->t('Few clouds - night'),
      '03d' => $this->t('Scattered clouds - day'),
      '03n' => $this->t('Scattered clouds - night'),
      '04d' => $this->t('Broken clouds - day'),
      '04n' => $this->t('Broken clouds - night'),
      '09d' => $this->t('Shower rain - day'),
      '09n' => $this->t('Shower rain - night'),
      '10d' => $this->t('Rain - day'),
      '10n' => $this->t('Rain - night'),
      '11d' => $this->t('Thunderstorm - day'),
      '11n' => $this->t('Thunderstorm - night'),
      '13d' => $this->t('Snow - day'),
      '13n' => $this->t('Snow - night'),
      '50d' => $this->t('Mist - day'),
      '50n' => $this->t('Mist - night'),
    );
  }

}
