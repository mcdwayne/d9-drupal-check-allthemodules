<?php

namespace Drupal\WeatherStation\Forms;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\weatherstation\Services\WeatherStationServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WeatherStationSettingsForm.
 *
 * @package Drupal\WeatherStation\Forms
 */
class WeatherStationSettingsForm extends ConfigFormBase {

  /**
   * Get weather service.
   *
   * @var \Drupal\weatherstation\Services\WeatherStationServices
   *   Weather service.
   */
  protected $weather;

  /**
   * Get weather service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   Config service.
   */
  protected $configFactory;

  /**
   * Get weather service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   *   Config service.
   */
  protected $file;

  /**
   * WeatherStationSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Module config.
   * @param \Drupal\weatherstation\Services\WeatherStationServices $weather
   *   Weather service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, WeatherStationServices $weather) {
    parent::__construct($configFactory);
    $this->config = $configFactory;
    $this->weather = $weather;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('weatherstation_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weatherstation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'weatherstation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('weatherstation.settings');

    // Api options.
    $form['api'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Global configuration'),
    );

    $form['api']['openweather_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#default_value' => $config->get('openweather_api_key'),
      '#description' => $this->t('Get api key. <a href="http://openweathermap.org/appid">Openweathemap.org</a>'),
    );

    $form['api']['lat'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('lat'),
      '#default_value' => $config->get('lat'),
    );

    $form['api']['lon'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('lon'),
      '#default_value' => $config->get('lon'),
    );

    $form['api']['weatherstation_id_container'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Selector to replace'),
      '#default_value' => $config->get('weatherstation_id_container'),
    );

    $form['api']['expired'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cache time'),
      '#default_value' => $config->get('expired'),
      '#description' => $this->t('Expired cache in minutes, recommended value is 10-60 min'),
    );

    $form['display'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Display options'),
    );

    // Display options.
    $form['display']['display_slogan'] = array(
      '#type' => 'checkbox',
      '#options' => array(0 => $this->t('Yes'), 1 => $this->t('No')),
      '#default_value' => $config->get('display_slogan'),
      '#title' => $this->t('Display slogan'),
    );

    $form['display']['display_temperature'] = array(
      '#type' => 'select',
      '#options' => array(
        'n' => $this->t('No'),
        'f' => $this->t('Fahrenheit'),
        'c' => $this->t('Celsius'),
        'k' => $this->t('Kelwin'),
      ),
      '#default_value' => $config->get('display_temperature'),
      '#title' => $this->t('Display temperature'),
    );

    $theme_dir = drupal_get_path('module', 'weatherstation') . '/assets/styles/css/theme/';
    $themes = file_scan_directory($theme_dir, '/.*\.css$/');

    foreach ($themes as $theme) {
      $weather_themes[$theme->uri] = $theme->name;
    }

    $form['display']['display_theme'] = array(
      '#type' => 'select',
      '#options' => $weather_themes,
      '#default_value' => $config->get('display_theme'),
      '#title' => $this->t('Display theme'),
    );

    // Weather type options.
    $weather_icons = $this->weather->getIcons();

    foreach ($weather_icons as $icon_code => $weather_icon) {

      $icon_config = $config->get($icon_code);
      $form[$icon_code . '_group'] = array(
        '#type' => 'details',
        '#title' => $weather_icon,
      );

      $form[$icon_code . '_group'][$icon_code . '_slogan'] = array(
        '#type' => 'textfield',
        '#group' => $icon_code . '_group',
        '#title' => $this->t('Slogan'),
        '#default_value' => $icon_config['slogan'],
      );
      $form[$icon_code . '_group'][$icon_code . '_image'] = array(
        '#type' => 'managed_file',
        '#group' => $icon_code . '_group',
        '#title' => $this->t('Image'),
        '#upload_location' => 'public://',
        '#default_value' => $icon_config['image'],
        '#description' => $this->t('Image for') . $weather_icon,
        '#upload_validators' => array(
          'file_validate_extensions' => array('gif png jpg jpeg'),
        ),
        '#states' => array(
          'visible' => array(
            ':input[name="image_type"]' => array('value' => $this->t('Upload New Image(s)')),
          ),
        ),
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO Create validation to config form.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config->getEditable('weatherstation.settings');
    $config->set('openweather_api_key', $form_state->getValue('openweather_api_key'))->save();
    $config->set('weatherstation_id_container', $form_state->getValue('weatherstation_id_container'))->save();
    $config->set('lon', $form_state->getValue('lon'))->save();
    $config->set('lat', $form_state->getValue('lat'))->save();
    $config->set('expired', $form_state->getValue('expired'))->save();

    $config->set('display_theme', $form_state->getValue('display_theme'))->save();
    $config->set('display_slogan', $form_state->getValue('display_slogan'))->save();
    $config->set('display_temperature', $form_state->getValue('display_temperature'))->save();

    $weather_icons = $this->weather->getIcons();
    foreach ($weather_icons as $icon_code => $weather_icon) {
      $config->set($icon_code . '.slogan', $form_state->getValue($icon_code . '_slogan'))->save();
      $config->set($icon_code . '.image', $form_state->getValue($icon_code . '_image'))->save();

      $file_usage = \Drupal::service('file.usage');

      $file = \Drupal\file\Entity\File::load($form_state->getValue($icon_code . '_image')[0]);
      $file_usage->add($file, 'weatherstation', 'weatherstation_icon', $icon_code);
    }
    parent::submitForm($form, $form_state);
  }

}
