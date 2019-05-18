<?php
/**
 * @file
 * Contains \Drupal\user\LocaleSettingsForm.
 */

namespace Drupal\google_analytics_lite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure locale settings for this site.
 */
class GoogleAnalyticsLiteForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return array('google_analytics_lite.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_lite.admin_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_lite.settings');

    $form['trackingId'] = array(
      '#type' => 'textfield',
      '#title' => t('Google Analytics Tracking ID'),
      '#default_value' => $config->get('trackingId'),
      '#description' => t('Enter Your Google Analytics Tracking ID. Format should be UA-12345678-9'),
    );

    $form['googleCodeVersion'] = array(
      '#type' => 'select',
      '#title' => t('Google Analytics Code Version'),
      '#options' => array(
        'legacy' => 'Legacy',
        'universal' => 'Universal'
      ),
      '#default_value' => $config->get('googleCodeVersion'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('google_analytics_lite.settings');
    $config
      ->set('trackingId', $values['trackingId'])
      ->set('googleCodeVersion', $values['googleCodeVersion'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
