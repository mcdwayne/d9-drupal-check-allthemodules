<?php

namespace Drupal\advertising_products\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdvertisingProductSettingsForm.
 *
 * @package Drupal\advertising_products\Form
 *
 * @ingroup advertising_products
 */
class AdvertisingProductSettingsForm extends ConfigFormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'advertising_products_settings';
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'advertising_products.settings'
    ];
  }

  /**
   * Defines the settings form for Advertising Product entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('advertising_products.settings');

    $form['run_cron'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Cron'),
      '#description' => $this->t('Select to enable cron. We recommend using the drush command instead.'),
      '#default_value' => $config->get('run_cron'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('advertising_products.settings')
      ->set('run_cron', $form_state->getValue('run_cron'))
      ->save();
  }

}
