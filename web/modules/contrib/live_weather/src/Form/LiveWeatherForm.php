<?php
/**
 * @file
 * Contains \Drupal\live_weather\Form\LiveWeatherForm.
 */

namespace Drupal\live_weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\live_weather\LiveWeatherInterface;

/**
 * Controller location for Live Weather Form.
 */
class LiveWeatherForm extends ConfigFormBase {

  /**
   * The Drupal configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Live weather control.
   *
   * @var Drupal\live_weather\LiveWeatherInterface
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
  public function __construct(ConfigFactoryInterface $config_factory, LiveWeatherInterface $live_weather) {
    $this->configFactory = $config_factory;
    $this->liveWeather = $live_weather;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('live_weather.controller')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'live_weather_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'live_weather.location',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['location'] = array(
      '#type' => 'textfield',
      '#title' => 'Location',
      '#description' => t('Enter Where On Earth IDentification of location. Find WOEID to use this url http://woeid.rosselliot.co.nz'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $locations = $this->configFactory->get('live_weather.location')->get('location');
    $location_value = $form_state->getValue('location');
    if (empty($location_value) || (!is_numeric($location_value))) {
      $form_state->setErrorByName('location', $this->t('location invalid1.'));
    }
    elseif (!empty($locations) && array_key_exists($location_value, $locations)) {
      $form_state->setErrorByName('location', $this->t('location already exists.'));
    }
    elseif (!empty($location_value)) {
      $output = $this->liveWeather->locationCheck($location_value, 'location', 'f');
      if (!empty($output)) {
        if (isset($output['location']) && !empty($output['location'])) {
          $city = $output['location']['city'] . ',' . $output['location']['region'] . ',' . $output['location']['country'];
          $locations[$location_value] = $city;
          $this->config('live_weather.location')
            ->set('location', $locations)
            ->save();
        }
        else {
          $form_state->setErrorByName('location', $this->t('location invalid2.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
