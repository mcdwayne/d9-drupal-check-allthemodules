<?php

/**
 * @file
 * Contains \Drupal\wunderground_weather\Plugin\Block\WeatherCurrentBlock.
 */

namespace Drupal\wunderground_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\wunderground_weather\WundergroundWeatherManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with current weather conditions.
 *
 * @Block(
 *  id = "wunderground_weather_current_block",
 *  admin_label = @Translation("Current weather conditions block"),
 *  module = "wunderground_weather"
 * )
 */
class WeatherCurrentBlock extends BlockBase implements ContainerFactoryPluginInterface {
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

    $form = parent::blockForm($form, $form_state);
    $form['location'] = [
      '#type' => 'fieldset',
      '#title' => t('Location'),
    ];

    // Autocomplete to get location.
    $form['location']['location_current'] = [
      '#title' => t('Location path'),
      '#type' => 'textfield',
      '#description' => t('Search for your city to determine the Wunderground location path.'),
      '#maxlength' => 120,
      '#required' => TRUE,
      '#autocomplete_route_name' => 'wunderground_weather.autocomplete',
      '#default_value' => isset($config['location_current']) ? $config['location_current'] : '',
    ];

    $form['temperature_scale'] = [
      '#title' => t('Show temperature in'),
      '#type' => 'radios',
      '#options' => [
        'c' => t('Celsius'),
        'f' => t('Fahrenheit'),
      ],
      '#default_value' => isset($config['temperature_scale']) ? $config['temperature_scale'] : 'c',
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

    $settings_current_defaults = [
      'weather' => 'weather',
      'conditions' => 'conditions',
      'temperature' => 'temperature',
      'feels_like' => 'feels_like',
      'wind' => 'wind',
    ];

    $form['current_fields'] = [
      '#title' => t('Fields'),
      '#type' => 'checkboxes',
      '#options' => [
        'weather' => t('Weather description'),
        'temperature' => t('Temperature'),
        'feels_like' => t('Feels like'),
        'wind' => t('Wind speed'),
      ],
      '#default_value' => isset($config['current_fields']) ? $config['current_fields'] : $settings_current_defaults,
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
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('location_current', $form_state->getValue(['location', 'location_current']));
    $this->setConfigurationValue('temperature_scale', $form_state->getValue(['temperature_scale']));
    $this->setConfigurationValue('windspeed_scale', $form_state->getValue(['windspeed_scale']));
    $this->setConfigurationValue('current_fields', $form_state->getValue(['current_fields']));
    $this->setConfigurationValue('icon_set', $form_state->getValue(['icon_set']));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get block configuration.
    $config = $this->getConfiguration();
    $location = $config['location_current'];
    $icon_set = $config['icon_set'];
    $temperature_scale = $config['temperature_scale'];
    $windspeed_scale = $config['windspeed_scale'];

    // Get all settings.
    $settings = $this->wundergroundWeatherManager->getSettings();

    preg_match('#\[(.*?)\]#', $location, $match);
    $path = $match[1];

    $options = [
      'api' => 'api',
      'key' => $settings->get('api_key'),
      'data_feature' => 'conditions',
      'language' => 'lang:' . strtoupper($settings->get('language')),
      'path' => $path,
    ];

    // Check if data is received.
    if ($weather = $this->wundergroundWeatherManager->requestData($options)) {
      // Get fields to be displayed.
      $fields = $this->configuration['current_fields'];

      // Build list items.
      $items = [];
      foreach ($fields as $field => $display) {
        if ($display && isset($weather->current_observation)) {
          // Calculate windspeed.
          switch ($windspeed_scale) {
            case 'mph':
              $windspeed = $weather->current_observation->wind_mph;
              break;

            case 'kph':
              $windspeed = $weather->current_observation->wind_kph;
              break;

            default:
              $wind_kph = $weather->current_observation->wind_kph;
              $windspeed = $this->wundergroundWeatherManager->windSpeedToBeaufort($wind_kph, 'kph');
              break;
          }

          switch ($field) {
            case 'weather':
              $items[$field] = $weather->current_observation->weather;
              break;

            case 'temperature':
              $temperature_property = 'temp_' . $temperature_scale;
              $items[$field] = t('Temperature: @temp °@scale', [
                '@temp' => $weather->current_observation->$temperature_property,
                '@scale' => strtoupper($temperature_scale),
              ]);
              break;

            case 'feels_like':
              $temperature_property = 'feelslike_' . $temperature_scale;
              $items[$field] = t('Feels like: @temp °@scale', [
                '@temp' => $weather->current_observation->$temperature_property,
                '@scale' => strtoupper($temperature_scale),
              ]);
              break;

            case 'wind':
              $items[$field] = t('Wind');
              $items[$field] .= ': ' . $windspeed . ' ' . $windspeed_scale;
              break;
          }
        }
      }

      // Get an unordered list.
      $item_list = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $items,
        '#title' => '',
        '#attributes' => [
          'class' => ['current-weather-summary'],
        ],
      ];

      // Get the weather icon.
      $variables = [
        '#theme' => 'wunderground_weather_current',
        '#iconset' => $icon_set,
        '#image' => [
          '#theme' => 'image',
          '#uri' => $this->wundergroundWeatherManager->getIconUrl($config['icon_set'], $weather->current_observation->icon),
          '#alt' => t('Weather in @city', ['@city' => $weather->current_observation->display_location->full]),
        ],
        '#summary' => $item_list,
      ];

      $output = render($variables);
    }
    else {
      // Return message if no data is retrieved.
      $output = t('No weather forecast available.');
    }

    return ['#children' => $output];
  }

}
