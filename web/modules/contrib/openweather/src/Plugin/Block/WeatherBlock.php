<?php

namespace Drupal\openweather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\openweather\WeatherService;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides a 'OpenWeatherBlock' Block.
 *
 * @Block(
 *   id = "open_weather_block",
 *   admin_label = @Translation("Open Weather Block"),
 * )
 */
class WeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\openweather\WeatherService
   */
  protected $weatherservice;
  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   *
   * @var string $weatherservice
   *   The information from the Weather service for this block.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WeatherService $weatherservice, ModuleHandlerInterface $module_handler, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->weatherservice = $weatherservice;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('openweather.weather_service'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['input_options'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select your option'),
      '#options' => array(
        'city_name' => $this->t('City Name'),
        'city_id' => $this->t('City Id'),
        'zip_code' => $this->t('Zip Code'),
        'geo_coord' => $this->t('Geographic Coordinates'),
      ),
      '#default_value' => !empty($config['input_options']) ? $config['input_options'] : 'city_name',
    );
    $form['input_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Enter the Value for selected option'),
      '#required' => TRUE,
      '#description' => $this->t('In case of geo coordinates please follow the format lat,lon for example: 130,131'),
      '#default_value' => !empty($config['input_value']) ? $config['input_value'] : '',
    );

    if ($this->moduleHandler->moduleExists("token")) {
      $form['token_help'] = array(
        '#type' => 'markup',
        '#token_types' => array('user'),
        '#theme' => 'token_tree_link',
      );
    }

    $form['count'] = array(
      '#type' => 'number',
      '#min' => '1',
      '#title' => $this->t('Enter the number count'),
      '#default_value' => !empty($config['count']) ? $config['count'] : '1',
      '#required' => TRUE,
      '#description' => $this->t('Select the count in case of hourlyforecast maximum value should be 36 and in case of daily forecast maximum value should be 7. in case of current weather forecast value is the default value'),
    );

    $form['display_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select your option'),
      '#options' => array(
        'current_details' => $this->t('Current Details'),
        'forecast_hourly' => $this->t('Forecast after 3 hours each'),
        'forecast_daily' => $this->t('Daily Forecast'),
      ),
      '#default_value' => !empty($config['display_type']) ? $config['display_type'] : 'current_details',
    );

    $weatherdata = array(
      'name' => $this->t('City Name'),
      'humidity' => $this->t('Humidity'),
      'temp_min' => $this->t('Temp Min'),
      'temp_max' => $this->t('Temp Max'),
      'coord' => $this->t('Coordinates'),
      'weather' => $this->t('Weather details include icon and description'),
      'temp' => $this->t('Current Temperature'),
      'pressure' => $this->t('Pressure'),
      'sea_level' => $this->t('Sea Level'),
      'grnd_level' => $this->t('Ground level'),
      'wind_speed' => $this->t('Wind Speed'),
      'wind_deg' => $this->t('Wind flow in degree'),
      'date' => $this->t('Date'),
      'time' => $this->t('Time'),
      'day' => $this->t('Day'),
      'country' => $this->t('Country'),
      'sunrise' => $this->t('Sunrise time'),
      'sunset' => $this->t('Sunset time'),
    );
    $form['weatherdata'] = array(
      '#type' => 'details',
      '#title' => $this->t('Output Option available for current weather'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['weatherdata']['items'] = array(
      '#type' => 'checkboxes',
      '#options' => $weatherdata,
      '#description' => $this->t('Select output data you want to display.'),
      '#default_value' => !empty($config['outputitems']) ? $config['outputitems'] : array(
        'name',
        'weather',
        'temp',
        'country',
        'time',
        'humidity',
        'date',
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($this->moduleHandler->moduleExists("token")) {
      $user = $form_state->getValue('account');
      $message = $this->token->replace($form_state->getValue('input_value'), array('user' => $user));
    }
    $this->setConfigurationValue('outputitems', $form_state->getValue('weatherdata')['items']);
    if (!empty($message)) {
      $this->setConfigurationValue('input_value', $message);
    }
    else {
      $this->setConfigurationValue('input_value', $form_state->getValue('input_value'));
    }
    $this->setConfigurationValue('count', $form_state->getValue('count'));
    $this->setConfigurationValue('input_options', $form_state->getValue('input_options'));
    $this->setConfigurationValue('display_type', $form_state->getValue('display_select'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $output = json_decode($this->weatherservice->getWeatherInformation($config), TRUE);
    if (!empty($output)) {
      switch ($config['display_type']) {
        case 'current_details':
          $build = $this->weatherservice->getCurrentWeatherInformation($output, $config);
          break;

        case 'forecast_hourly':
          $build = $this->weatherservice->getHourlyForecastWeatherInformation($output, $config);
          break;

        case 'forecast_daily':
          $build = $this->weatherservice->getDailyForecastWeatherInformation($output, $config);
          break;
      }
      return $build;
    }

  }

}
