<?php

/**
 * @file
 * Contains \Drupal\weathercomau\Form\WeathercomauAdmin.
 */

namespace Drupal\weathercomau\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WeathercomauAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weathercomau_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['weathercomau.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('weathercomau.settings');

    $form['units'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Display units'),
      '#description' => $this->t('Specify which units should be used for displaying the weather data.'),
    );

    $form['units']['weathercomau_temperature_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Temperature'),
      '#default_value' => $config->get('temperature_unit'),
      '#options' => array(
        'celsius' => $this->t('Celsius'),
        'fahrenheit' => $this->t('Fahrenheit'),
        'celsiusfahrenheit' => $this->t('Celsius / Fahrenheit'),
        'fahrenheitcelsius' => $this->t('Fahrenheit / Celsius'),
      ),
      '#required' => TRUE,
    );

    $form['units']['weathercomau_windspeed_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Wind speed'),
      '#default_value' => $config->get('windspeed_unit'),
      '#options' => array(
        'kmh' => $this->t('km/h'),
        'mph' => $this->t('mph'),
        'knots' => $this->t('Knots'),
        'mps' => $this->t('meter/s'),
        'beaufort' => $this->t('Beaufort'),
      ),
      '#required' => TRUE,
    );

    $form['units']['weathercomau_pressure_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Pressure'),
      '#default_value' => $config->get('pressure_unit'),
      '#options' => array(
        'hpa' => $this->t('hPa'),
        'kpa' => $this->t('kPa'),
        'inhg' => $this->t('inHg'),
        'mmhg' => $this->t('mmHg'),
      ),
      '#required' => TRUE,
    );

    $form['units']['weathercomau_rainfall_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Rainfall'),
      '#default_value' => $config->get('rainfall_unit'),
      '#options' => array(
        'mm' => $this->t('Millimetres'),
        'inches' => $this->t('Inches'),
      ),
      '#required' => TRUE,
    );

    $form['caching'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Caching'),
    );

    $form['caching']['weathercomau_cache_lifetime'] = array(
      '#type' => 'select',
      '#title' => $this->t('Cache lifetime'),
      '#description' => $this->t('The <a href="!link">RSS Weather Feed Quota Policy</a> restricts the number of queries made per day. We recommend setting this cache option to at least 1 hour.', array(
        '!link' => Url::fromUri('http://www.weather.com.au/about/rss')->toString(),
      )),
      '#options' => array(
        900 => '15 minutes',
        1800 => '30 minutes',
        3600 => '1 hour',
        10800 => '3 hours',
        21600 => '6 hours',
        43200 => '12 hours',
        86400 => '24 hours',
      ),
      '#default_value' => $config->get('cache_lifetime'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('weathercomau.settings')
      ->set('temperature_unit', $form_state->getValue('weathercomau_temperature_unit'))
      ->set('windspeed_unit', $form_state->getValue('weathercomau_windspeed_unit'))
      ->set('pressure_unit', $form_state->getValue('weathercomau_pressure_unit'))
      ->set('rainfall_unit', $form_state->getValue('weathercomau_rainfall_unit'))
      ->set('cache_lifetime', $form_state->getValue('weathercomau_cache_lifetime'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
