<?php

namespace Drupal\password_strength\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class PasswordStrengthSettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'password_strength_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'password_strength.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('password_strength.settings');
    $form = array();

    //matchers
    $plugin_manager = \Drupal::service('plugin.manager.password_strength.password_strength_matcher');
    $all_plugins = $plugin_manager->getDefinitions();

    $all_matchers = array();
    foreach ($all_plugins as $plugin) {
      $id = $plugin['id'];
      $all_matchers[$id] = $plugin['title'];
    }

    $form['matchers'] = array(
      '#title' => 'Matchers',
      '#type' => 'checkboxes',
      '#options' => $all_matchers,
      '#default_value' => $config->get('enabled_matchers'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('password_strength.settings')
      ->set('enabled_matchers', $form_state->getValue('matchers'))
      ->save();
    drupal_set_message('Password Strength settings have been stored');
    parent::submitForm($form, $form_state);
  }
}