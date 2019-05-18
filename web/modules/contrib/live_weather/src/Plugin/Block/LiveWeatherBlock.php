<?php
/**
 * @file
 * Contains \Drupal\live_weather\Plugin\Block\LiveWeatherBlock.
 */

namespace Drupal\live_weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Drupal\live_weather\LiveWeatherInterface;

/**
 * Provides a 'Live Weather' block.
 *
 * @Block(
 *   id = "live_weather_block",
 *   admin_label = @Translation("Live Weather block"),
 * )
 */
class LiveWeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Live weather controller.
   *
   * @var Drupal\live_weather\Controller\LiveWeatherController
   */
  protected $liveWeather;

  /**
   * Constructs a location form object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory holding resource settings.
   * @param Drupal\live_weather\LiveWeatherInterface $live_weather
   *   The controls of Live weather.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, LiveWeatherInterface $live_weather) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->liveWeather = $live_weather;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
    $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('live_weather.controller')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $location_list = $this->configFactory->get('live_weather.location')->get('location');
    $default = array_keys($location_list);
    if (isset($this->configuration['list']['list'])) {
      $default = array_values($this->configuration['list']['list']);
    }
    $form['location'] = array(
      '#type' => 'details',
      '#title' => $this->t('Location list'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['location']['list'] = array(
      '#type' => 'checkboxes',
      '#multiple' => TRUE,
      '#options' => $location_list,
      '#default_value' => $default,
      '#description' => $this->t('Select locations to display in block'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['list'] = $form_state->getValue('location');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration['list'];
    $location_list = $this->configFactory->get('live_weather.location')->get('location');
    $location_list = array_keys($location_list);
    $location_list = array_intersect($config['list'], $location_list);
    $settings = $this->configFactory->get('live_weather.settings')->get('settings');
    
    
    $html = array();
    $feed = array();
    $feed_data = $this->liveWeather;
    $i = 0;
    if (!empty($location_list)) {
      foreach ($location_list as $woeid) {
        $data = $feed_data->locationCheck($woeid, ' * ', $settings['unit']);
        if (is_array($data) && !empty($data)) {
          if (!empty($data['location']['city'])) {
            $temp = Html::escape($data['current_observation']['condition']['temperature']);
            $date = Html::escape($data['current_observation']['pubDate']);
            $feed_sunrise = Html::escape($data['current_observation']['astronomy']['sunrise']);
            $feed_sunset = Html::escape($data['current_observation']['astronomy']['sunset']);
            $daynight = $feed_data->checkDayNight($date, $feed_sunrise, $feed_sunset);
            $wind_direction = $feed_data->windDirection(Html::escape($data['current_observation']['wind']['direction']));
            $html[$i]['location'] = Html::escape($data['location']['city']) . ', ' . Html::escape($data['location']['region']) . ', ' . Html::escape($data['location']['country']);
            $html[$i]['temperature'] = $settings['unit'] == 'C'? round(($temp - 32)*5/9) : $temp;
            $html[$i]['temperature_unit'] = $settings['unit'];
            $html[$i]['text'] = Html::escape($data['current_observation']['condition']['text']);
            if ($settings['image']) {
              $html[$i]['image'] = 'https://s.yimg.com/zz/combo?a/i/us/nws/weather/gr/' . Html::escape($data['current_observation']['condition']['code']) . $daynight;
            }
            if ($settings['wind']) {
              $html[$i]['wind'] = Html::escape($data['current_observation']['wind']['speed']) . ' mph ' . $wind_direction;
            }
            if ($settings['humidity']) {
              $html[$i]['humidity'] = Html::escape($data['current_observation']['atmosphere']['humidity']);
            }
            if ($settings['visibility']) {
              $html[$i]['visibility'] = Html::escape($data['current_observation']['atmosphere']['visibility']);
            }
            if ($settings['sunrise']) {
              $html[$i]['sunrise'] = $feed_sunrise;
            }
            if ($settings['sunset']) {
              $html[$i]['sunset'] = $feed_sunset;
            }
          }
          $i++;
        } 
      }
    }
    return array(
      '#theme' => 'live_weather',
      '#weather_detail' => $html,
      '#cache' => array('max-age' => $settings['cache']),
    );
  }

}
