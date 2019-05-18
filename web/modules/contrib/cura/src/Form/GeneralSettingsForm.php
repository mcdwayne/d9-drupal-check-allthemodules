<?php

namespace Drupal\cura\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * General settings for the Cura Childcare Suite.
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cura_general_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cura.general.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cura.general.settings');

    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => 'org'
    ];
    $form['org'] = [
      '#type' => 'details',
      '#title' => $this->t('Organisation'),
      '#tree' => TRUE,
      '#group' => 'settings'
    ];
    $form['org']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Organisation name'),
      '#default_value' => $config->get('org.name')
    );

    $form['org']['name_short'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Short name'),
      '#default_value' => $config->get('org.name_short')
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration
    // drupal_set_message(print_r($form_state->getValues(), TRUE));
    $this->configFactory->getEditable('cura.general.settings')
      ->set('org.name', $form_state->getValue(array('org', 'name')))
      ->set('org.name_short', $form_state->getValue(array('org', 'name_short')))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
