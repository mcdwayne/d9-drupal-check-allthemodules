<?php

/**
 * @file
 * Contains \Drupal\naming\Form\NamingSettingsForm.
 */

namespace Drupal\naming\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures naming settings.
 */
class NamingSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'naming_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['naming.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('naming.settings');
    $form['disable_machine_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic generation of machine names'),
      '#description' => $this->t('Disabling the automatic generation of machine names will require site builders to manually enter and review all machine names.'),
      '#default_value' => $config->get('disable_machine_name'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('naming.settings');
    $config->set('disable_machine_name', $form_state->getValue('disable_machine_name'));
    $config->save();
    drupal_flush_all_caches();
    parent::submitForm($form, $form_state);
  }

}
