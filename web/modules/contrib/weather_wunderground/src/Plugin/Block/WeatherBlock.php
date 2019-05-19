<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Example: configurable text string' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "wunderground_weather",
 *   admin_label = @Translation("Weather from wunderground"),
 *   module = "weather"
 * )
 */
class WeatherBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration. This configuration
   * determines the block's behavior when a block is initially placed in a
   * region. Default values for the block configuration form should be added to
   * the configuration array. System default configurations are assembled in
   * BlockBase::__construct() e.g. cache setting and block title visibility.
   *
   * @see \Drupal\block\BlockBase::__construct()
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'weather_url' => 'http://api.wunderground.com/api/',
      'weather_iconset_url' => 'http://icons.wxug.com/i/c/c/',
      'country' => 'Ukraine',
      'city' => 'Kiev',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This method defines form elements for custom block configuration. Standard
   * block configuration fields are added by BlockBase::buildConfigurationForm()
   * (block title and title visibility) and BlockFormController::form() (block
   * visibility settings).
   *
   * @see \Drupal\block\BlockBase::buildConfigurationForm()
   * @see \Drupal\block\BlockFormController::form()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api key'),
      '#description' => $this->t('wunderground.com'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];
    $form['weather_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('api url'),
      '#default_value' => $this->configuration['weather_url'],
      '#required' => TRUE,
    ];
    $form['weather_iconset_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Iconset url'),
      '#description' => $this->t('Icons for weather'),
      '#default_value' => $this->configuration['weather_iconset_url'],
      '#required' => TRUE,
    ];
    $form['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#description' => $this->t('Country'),
      '#default_value' => $this->configuration['country'],
      '#required' => TRUE,
    ];
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#description' => $this->t('City'),
      '#default_value' => $this->configuration['city'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['api_key']  = $form_state->getValue('api_key');
    $this->configuration['weather_url']  = $form_state->getValue('weather_url');
    $this->configuration['weather_iconset_url']  = $form_state->getValue('weather_iconset_url');
    $this->configuration['country']  = $form_state->getValue('country');
    $this->configuration['city']  = $form_state->getValue('city');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $url = "{$this->configuration['weather_url']}{$this->configuration['api_key']}/geolookup/conditions/forecast/q/{$this->configuration['country']}/{$this->configuration['city']}.json";
    $winds = [
      'East' => $this->t('eastern', [], ['context' => 'Wind']),
      'North' => $this->t('north', [], ['context' => 'Wind']),
      'South' => $this->t('south', [], ['context' => 'Wind']),
      'West' => $this->t('west', [], ['context' => 'Wind']),
      'NNE' => $this->t('north / north-easterly', [], ['context' => 'Wind']),
      'ENE' => $this->t('east / north-easterly', [], ['context' => 'Wind']),
      'NE' => $this->t('northeastern', [], ['context' => 'Wind']),
      'SE' => $this->t('south / easter', [], ['context' => 'Wind']),
      'SSE' => $this->t('south / south-easterly', [], ['context' => 'Wind']),
      'Variable' => $this->t('Variable', [], ['context' => 'Wind']),
    ];
    $weather = NULL;
    try {
      $json = \GuzzleHttp\json_decode(file_get_contents($url));
      if(isset($json->response->error)) {
        \Drupal::logger('weather')->error('Can not get weather information. api returned error @type @description',
          ['@type' => $json->response->error->type, '@description' => $json->response->error->description]);

      } else {
        $weather = new \stdClass();
        $weather->low_celsius = $json->forecast->simpleforecast->forecastday[0]->low->celsius;
        $weather->high_celsius = $json->forecast->simpleforecast->forecastday[0]->high->celsius;
        $weather->forecastday = [];
        $weather->current_temp_celcius = $json->current_observation->temp_c;
        $weather->current_pressure_rt = number_format($json->current_observation->pressure_mb / 1.3333, 1);
        $weather->current_wind_dir = $json->current_observation->wind_dir;
        $weather->current_wind_dir_verbose = isset($winds[$weather->current_wind_dir])?$winds[$weather->current_wind_dir]:$weather->current_wind_dir;
        $weather->current_wind_mph = $json->current_observation->wind_mph;
        $weather->current_wind_kph = $json->current_observation->wind_kph;
        $weather->current_relative_humidity = $json->current_observation->relative_humidity;

        $weather->current_weather_icon = $this->configuration['weather_iconset_url'].$json->forecast->simpleforecast->forecastday[0]->icon.'.png';
        $weather->city = $json->current_observation->display_location->city;
        foreach ($json->forecast->simpleforecast->forecastday as $key => $data) {
          if($key === 0) {
            continue;
          }
          $day = new \stdClass();
          $day->date_verbose = strftime('%d %B', strtotime($data->date->pretty));
          $day->low_celsius = $data->low->celsius;
          $day->high_celsius = $data->high->celsius;
          $day->icon = $this->configuration['weather_iconset_url'].$data->icon.'.png';
          $weather->forecastday[] = $day;
        }
      }
    } catch (\InvalidArgumentException $ex) {
      \Drupal::logger('weather')->error('Can not get weather information. Invalid response from api');
      $weather = NULL;
    }
    return [
      '#theme' => 'weather_block',
      '#weather' => $weather,
      '#attached' => [
        'library' => [
          'weather/weather',
        ],
      ],
      '#cache' => [
        'max-age' => 7200,
      ],
    ];
  }

}