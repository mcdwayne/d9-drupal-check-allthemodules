<?php

namespace Drupal\custom_configurations\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CustomConfigurationsSettingsForm.
 *
 * @package Drupal\custom_configurations\Form
 */
class CustomConfigurationsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_configurations_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['custom_configurations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('custom_configurations.settings')
      ->set('menu_title', $form_state->getValue('menu_title'))
      ->save();
    $cache_bins = Cache::getBins();
    $cache_bins['menu']->deleteAll();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['menu_title'] = [
      '#type' => 'textfield',
      '#title' => 'Title for the parent menu item',
      '#placeholder' => 'Custom configurations',
      '#description' => $this->t('If empty, the "Custom configurations" will be used.'),
      '#default_value' => $this->config('custom_configurations.settings')->get('menu_title'),
    ];
    return $form;
  }

}