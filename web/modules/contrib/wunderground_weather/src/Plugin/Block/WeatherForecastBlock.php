<?php

/**
 * @file
 * Contains \Drupal\wunderground_weather\Plugin\Block\WeatherForecastBlock.
 */

namespace Drupal\wunderground_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wunderground_weather\WundergroundWeatherManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a with a five day weather forecast.
 *
 * @Block(
 *  id = "wunderground_weather_forecast_block",
 *  admin_label = @Translation("Weather forecast block")
 * )
 */
class WeatherForecastBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Methods to make an API call and tool to handle the output.
   *
   * @var \Drupal\wunderground_weather\WundergroundWeatherManager
   */
  protected $wundergroundWeatherManager;

  /**
   * WeatherCurrentBlock constructor.
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
    /** @var \Drupal\wunderground_weather\WundergroundWeatherManager $wunderground_weather_tools */
    $wunderground_weather_tools = $container->get('wunderground_weather.manager');

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $wunderground_weather_tools
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
    $form['location']['location_forecast'] = [
      '#title' => t('Location path'),
      '#type' => 'textfield',
      '#description' => t('Search for your city to determine the Wunderground location path.'),
      '#maxlength' => 120,
      '#required' => TRUE,
      '#autocomplete_route_name' => 'wunderground_weather.autocomplete',
      '#default_value' => isset($config['location_forecast']) ? $config['location_forecast'] : '',
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

    $form['forecast_fields'] = [
      '#title' => t('Fields'),
      '#type' => 'checkboxes',
      '#options' => [
        'image' => t('Weather icons'),
        'conditions' => t('Weather description'),
        'temperature' => t('Temperature'),
        'rain' => t('Chance of rain'),
        'wind' => t('Wind speed'),
      ],
      '#default_value' => isset($config['forecast_fields']) ? $config['forecast_fields'] : $settings_forecast_defaults,
    ];

    $form['number_of_days'] = [
      '#title' => t('For how many days you would like to display a forecast'),
      '#description' => t('You can display up to 10 days'),
      '#type' => 'textfield',
      '#default_value' => isset($config['number_of_days']) ? $config['number_of_days'] : 3,
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
      '#default_value' => isset($config['icon_set']) ? $config['icon_set'] : 'a',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if ($form_state->getValue(['number_of_days']) > 10) {
      $form_state->setErrorByName('number_of_days', $this->t('You cannot display more than 10 days'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('location_forecast', $form_state->getValue(['location', 'location_forecast']));
    $this->setConfigurationValue('temperature_scale', $form_state->getValue(['temperature_scale']));
    $this->setConfigurationValue('windspeed_scale', $form_state->getValue(['windspeed_scale']));
    $this->setConfigurationValue('forecast_fields', $form_state->getValue(['forecast_fields']));
    $this->setConfigurationValue('number_of_days', $form_state->getValue(['number_of_days']));
    $this->setConfigurationValue('icon_set', $form_state->getValue(['icon_set']));
  }

  /**
   * {@inheritdoc}
   *
   * @todo use render array instead of theme function.
   */
  public function build() {
    // Get block configuration.
    $config = $this->getConfiguration();
    $location = $config['location_forecast'];
    $number_of_days = $config['number_of_days'];
    $icon_set = $config['icon_set'];

    // Get all settings.
    $settings = $this->wundergroundWeatherManager->getSettings();

    preg_match('#\[(.*?)\]#', $location, $match);
    $path = $match[1];
    $options = [
      'api' => 'api',
      'key' => $settings->get('api_key'),
      'data_feature' => 'forecast10day',
      'language' => 'lang:' . strtoupper($settings->get('language')),
      'path' => $path,
    ];

    $data = $this->wundergroundWeatherManager->requestData($options);
    $days = isset($data->forecast) ? $data->forecast->simpleforecast->forecastday : [];

    $variables['#theme'] = 'wunderground_weather_forecast';
    $variables['#icon_set'] = $icon_set;
    $variables['#data'] = array_slice($days, 0, $number_of_days);
    $variables['#fields'] = $config['forecast_fields'];
    $variables['#temperature_scale'] = $config['temperature_scale'];
    $variables['#windspeed_scale'] = $config['windspeed_scale'];

    // Check if data is received.
    if ($data) {
      $output = render($variables);
    }
    else {
      // Return message if no data is retrieved.
      $output = t('No weather forecast available.');
    }

    return [
      '#children' => $output,
    ];
  }

}
