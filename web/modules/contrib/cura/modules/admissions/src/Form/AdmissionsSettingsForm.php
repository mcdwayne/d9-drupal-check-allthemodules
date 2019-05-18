<?php

namespace Drupal\cura_admissions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admissions settings for the Cura Childcare Suite.
 */
class AdmissionsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cura_admissions_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cura.admissions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cura.admissions.settings');

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'general'
    ];
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#tree' => TRUE,
      '#group' => 'settings'
    ];
    $form['general']['textfield1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield One'),
      '#default_value' => $config->get('general.textfield1')
    ];
    $form['general']['textfield2'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Textfield Two'),
      '#default_value' => $config->get('general.textfield2')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
//    drupal_set_message(print_r($form_state->getValues(), TRUE));
    $this->configFactory->getEditable('cura.admissions.settings')
      ->set('general.textfield1', $form_state->getValue(array('general', 'textfield1')))
      ->set('general.textfield2', $form_state->getValue(array('general', 'textfield2')))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
