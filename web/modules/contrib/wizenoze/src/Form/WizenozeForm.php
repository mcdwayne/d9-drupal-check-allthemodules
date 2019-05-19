<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure register form settings for this site.
 */
class WizenozeForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wizenoze_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wizenoze.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wizenoze.settings');

    $form['authorization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wizenoze Authorization Key'),
      '#required' => TRUE,
      '#default_value' => $config->get('authorization'),
    ];

    $form['age'] = [
      '#type' => 'number',
      '#title' => $this->t('Wizenoze Default Child Age'),
      '#default_value' => $config->get('age'),
    ];

    $form['readabilityLevel'] = [
      '#type' => 'number',
      '#title' => $this->t('Wizenoze Default Readability Level'),
      '#default_value' => $config->get('readabilityLevel'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('wizenoze.settings');
    $config->set('authorization', $form_state->getValue('authorization'))
      ->set('age', $form_state->getValue('age'))
      ->set('readabilityLevel', $form_state->getValue('readabilityLevel'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
