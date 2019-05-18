<?php
/**
 * @file
 * Contains \Drupal\live_weather\Form\LiveWeatherSettingsForm.
 */

namespace Drupal\live_weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Controller location for Live Weather Settings Form.
 */
class LiveWeatherSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'live_weather_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'live_weather.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory->get('live_weather.settings')->get('settings');
    $form['#tree'] = TRUE;
       $form['settings']['app_id'] = array(
      '#type' => 'textfield',
      '#title' => 'Yahoo App ID',
      '#required' => TRUE,
      '#default_value' => $settings['app_id'],
      '#description' => t('Please enter your Yahoo App ID.'),
    );
    $form['settings']['consumer_key'] = array(
      '#type' => 'textfield',
      '#title' => 'Yahoo App Consumer Key',
      '#required' => TRUE,
      '#default_value' => $settings['consumer_key'],
      '#description' => t('Please enter your Yahoo App Consumer Key.'),
    );
    $form['settings']['consumer_secret'] = array(
      '#type' => 'textfield',
      '#title' => 'Yahoo App Consumer Secret Key',
      '#required' => TRUE,
      '#default_value' => $settings['consumer_secret'],
      '#description' => t('Please enter your Yahoo App Consumer Secret Key'),
    );
    $form['settings']['unit'] = array(
      '#type' => 'select',
      '#title' => 'Unit',
      '#options' => array('F' => t('Fahrenheit'), 'C' => t('Celsius')),
      '#default_value' => $settings['consumer_secret'],
      '#description' => t('Select Fahrenheit or Celsius for temperature unit.'),
    );
    $form['settings']['image'] = array(
      '#type' => 'select',
      '#title' => 'Image',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['image'],
      '#description' => t('Select Yes to show Forcast Image.'),
    );
    $form['settings']['wind'] = array(
      '#type' => 'select',
      '#title' => 'Wind',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['wind'],
      '#description' => t('Select Yes to show wind speed.'),
    );
    $form['settings']['humidity'] = array(
      '#type' => 'select',
      '#title' => 'Humidity',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['humidity'],
      '#description' => t('Select Yes to show humidity level.'),
    );
    $form['settings']['visibility'] = array(
      '#type' => 'select',
      '#title' => 'Visibility',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['visibility'],
      '#description' => t('Select Yes to show visibility level.'),
    );
    $form['settings']['sunrise'] = array(
      '#type' => 'select',
      '#title' => 'Sunrise',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['sunrise'],
      '#description' => t('Select Yes to show sunrise time.'),
    );
    $form['settings']['sunset'] = array(
      '#type' => 'select',
      '#title' => 'Sunset',
      '#options' => array(FALSE => t('No'), TRUE => t('Yes')),
      '#default_value' => $settings['sunset'],
      '#description' => t('Select Yes to show sunset time.'),
    );
    $form['settings']['cache'] = array(
      '#type' => 'select',
      '#title' => 'Cache',
      '#options' =>
      array(
        0 => t('No Cache'),
        1800 => t('30 min'),
        3600 => t('1 hour'),
        86400 => t('One day'),
      ),
      '#default_value' => $settings['cache'],
      '#description' => t('Time for cache the block'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValue('settings');
    $this->config('live_weather.settings')
      ->set('settings', $form_value)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
