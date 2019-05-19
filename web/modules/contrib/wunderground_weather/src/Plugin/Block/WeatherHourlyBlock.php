<?php

/**
 * @file
 * Contains \Drupal\wunderground_weather\Plugin\Block\WeatherHourlyBlock.
 */

namespace Drupal\wunderground_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wunderground_weather\WundergroundWeatherManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a with an hourly weather forecast.
 *
 * @Block(
 *  id = "wunderground_weather_hourly_block",
 *  admin_label = @Translation("Weather hourly block")
 * )
 */
class WeatherHourlyBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Methods to make an API call and tool to handle the output.
   *
   * @var \Drupal\wunderground_weather\WundergroundWeatherManager
   */
  protected $wundergroundWeatherManager;

  /**
   * WeatherHourlyBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\wunderground_weather\WundergroundWeatherManager $wunderground_weather_manager
   *   Methods to make an API call and tool to handle the output.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WundergroundWeatherManager $wunderground_weather_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->wundergroundWeatherManager = $wunderground_weather_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\wunderground_weather\WundergroundWeatherManager $wunderground_weather_manager */
    $wunderground_weather_manager = $container->get('wunderground_weather.manager');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $wunderground_weather_manager

    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['location'] = [
      '#type' => 'fieldset',
      '#title' => t('Location'),
    ];

    // Autocomplete to get location.
    $form['location']['location'] = [
      '#title' => t('Location path'),
      '#type' => 'textfield',
      '#description' => t('Search for your city to determine the Wunderground location path.'),
      '#maxlength' => 120,
      '#required' => TRUE,
      '#autocomplete_route_name' => 'wunderground_weather.autocomplete',
      '#default_value' => isset($config['location']) ? $config['location'] : '',
    ];

    $form['temperature_scale'] = [
      '#title' => t('Show temperature in'),
      '#type' => 'radios',
      '#options' => [
        'c' => t('Celsius'),
        'f' => t('Fahrenheit'),
      ],
      '#default_value' => empty($this->configuration['temperature_scale']) ? 'c' : $this->configuration['temperature_scale'],
    ];

    $form['windspeed_scale'] = [
      '#title' => t('Show wind speed in'),
      '#type' => 'radios',
      '#options' => [
        'bft' => t('Beaufort'),
        'mph' => t('Miles per hour'),
        'kph' => t('Kilometers per hour'),
      ],
      '#default_value' => isset($config['windspeed_scale']) ? $config['windspeed_scale'] : 'bft',
    ];

    $settings_forecast_defaults = [
      'image' => 'image',
      'conditions' => 'conditions',
      'temperature' => 'temperature',
      'rain' => 'rain',
      'wind' => 'wind',
    ];

    $form['used_fields'] = [
      '#title' => t('Fields'),
      '#type' => 'checkboxes',
      '#options' => $this->getAvailableFields(),
      '#default_value' => isset($config['forecast_fields']) ? $config['forecast_fields'] : $settings_forecast_defaults,
    ];

    $form['number_of_hours'] = [
      '#title' => t('How many hours would you like to display'),
      '#description' => t('You can display up to 36 hours'),
      '#type' => 'number',
      '#default_value' => isset($config['number_of_hours']) ? $config['number_of_hours'] : 3,
      '#size' => 2,
      '#maxlength' => 2,
      '#required' => TRUE,
    ];

    $icons = [];
    foreach (range('a', 'k') as $set) {
      $icons[$set] = $this->wundergroundWeatherManager->getIconSetSample($set);
    }

    $form['icon_set'] = [
      '#titel' => t('Select an icons set'),
      '#type' => 'radios',
      '#options' => $icons,
      '#default_value' => isset($config['icon_set']) ? $config['icon_set'] : 'k',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue(['number_of_hours']) > 36) {
      $form_state->setErrorByName('number_of_hours', $this->t('You cannot display more than 36 hours'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('location', $form_state->getValue(['location', 'location']));
    $this->setConfigurationValue('temperature_scale', $form_state->getValue(['temperature_scale']));
    $this->setConfigurationValue('windspeed_scale', $form_state->getValue(['windspeed_scale']));
    $this->setConfigurationValue('used_fields', $form_state->getValue(['used_fields']));
    $this->setConfigurationValue('number_of_hours', $form_state->getValue(['number_of_hours']));
    $this->setConfigurationValue('icon_set', $form_state->getValue(['icon_set']));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get block configuration.
    $config = $this->getConfiguration();
    $location = $config['location'];
    $used_fields = $config['used_fields'];
    $available_fields = $this->getAvailableFields();

    // Get all settings.
    $settings = $this->wundergroundWeatherManager->getSettings();

    preg_match('#\[(.*?)\]#', $location, $match);
    $path = $match[1];
    $options = [
      'api' => 'api',
      'key' => $settings->get('api_key'),
      'data_feature' => 'hourly',
      'language' => 'lang:' . strtoupper($settings->get('language')),
      'path' => $path,
    ];

    $data = $this->wundergroundWeatherManager->requestData($options);

    $rows = [];
    $hours = isset($data->hourly_forecast) ? $data->hourly_forecast : [];
    if ($hours) {
      foreach ($hours as $i => $hour) {
        if ($i >= $config['number_of_hours']) {
          break;
        }

        $row['data']['hour'] = $hour->FCTTIME->hour . ':' . $hour->FCTTIME->min;
        foreach ($used_fields as $field) {
          if ($field) {
            $row['data'][$field] = call_user_func([$this, 'get' . ucfirst($field)], $hour);
          }
        }
        $rows[] = $row;
      }
    }

    $header[] = t('Hours');
    foreach ($used_fields as $used_field) {
      if ($used_field) {
        $header[] = $available_fields[$used_field];
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * An array of available fields to display.
   *
   * @return array
   *   An array of available fields.
   */
  protected function getAvailableFields() {
    return [
      'image' => t('Weather icons'),
      'conditions' => t('Weather description'),
      'temperature' => t('Temperature'),
      'rain' => t('Chance of rain'),
      'wind' => t('Wind speed'),
    ];
  }

  /**
   * Render a weather icon image.
   *
   * @param \stdClass $hour
   *   The hour for that holds the icon variable.
   *
   * @return string|null
   *   The rendered element.
   */
  protected function getImage(\stdClass $hour) {
    $config = $this->getConfiguration();

    $icon = [
      '#theme' => 'image',
      '#uri' => $this->wundergroundWeatherManager->getIconUrl($config['icon_set'], $hour->icon),
      '#alt' => $hour->condition,
    ];

    return render($icon);
  }

  /**
   * Get the weather conditions for a specific hour.
   *
   * @param \stdClass $hour
   *   The hour for that holds the condition variable.
   *
   * @return string
   *   A string representing a weather condition.
   */
  protected function getConditions(\stdClass $hour) {
    return $hour->condition;
  }

  /**
   * Get a formatted temperature string.
   *
   * @param \stdClass $hour
   *   The hour for that holds the temperature variable.
   *
   * @return string
   *   A formatted temperature
   */
  protected function getTemperature(\stdClass $hour) {
    $config = $this->getConfiguration();
    $temp = $config['temperature_scale'] === 'c' ? $hour->temp->metric : $hour->temp->english;
    return $temp . '°' . strtoupper($config['temperature_scale']);
  }

  /**
   * Get the change of rain.
   *
   * @param \stdClass $hour
   *   The hour for that holds the pop variable.
   *
   * @return string
   *   The change of rain for a specific hour.
   */
  protected function getRain(\stdClass $hour) {
    return $hour->pop . '%';
  }

  /**
   * Get the wind speed for a specific hour.
   *
   * @param \stdClass $hour
   *   The hour for that holds the wind speed variable.
   *
   * @return string
   *   The windspeed in bft
   *
   */
  protected function getWind(\stdClass $hour) {
    $config = $this->getConfiguration();
    $windspeed_scale = $config['windspeed_scale'];

    switch ($windspeed_scale) {
      case 'mph':
        $windspeed = $hour->wspd->english;
        break;

      case 'kph':
        $windspeed = $hour->wspd->metric;
        break;

      default:
        $wind_kph = $hour->wspd->metric;
        $windspeed = $this->wundergroundWeatherManager->windSpeedToBeaufort($wind_kph, 'kph');
        break;
    }
    return $windspeed . ' ' . $windspeed_scale;
  }

}
