<?php
/**
 * @file
 * Contains \Drupal\shurly\Form\ShurlyAnalyticsSettingsForm.
 */

namespace Drupal\shurly_analytics\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element;

class ShurlyAnalyticsSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
   public function __construct(ConfigFactoryInterface $config_factory) {
     parent::__construct($config_factory);
   }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shurly_analytics.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shurly_analytics_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    global $base_url;

    $form['shurlyanalytics_account'] = array(
        '#title' => t('Enter you Identifiant (ID) Web Property'),
        '#type' => 'textfield',
        '#default_value' => \Drupal::config('google_analytics.settings')->get('account'),
        '#size' => 15,
        '#maxlength' => 20,
        '#required' => TRUE,
      );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('shurly_analytics.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }
}
