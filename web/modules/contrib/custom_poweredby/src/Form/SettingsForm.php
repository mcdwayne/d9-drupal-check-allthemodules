<?php

namespace Drupal\custom_poweredby\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\custom_poweredby\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_poweredby.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_poweredby_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_poweredby.settings');
    $form['poweredby_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Poweredby Text'),
      '#default_value' => $config->get('poweredby_text'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  /* public function validateForm(array &$form,FormStateInterface $form_state) {
  parent::validateForm($form, $form_state);
  } */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('custom_poweredby.settings')
      ->set('poweredby_text', $form_state->getValue('poweredby_text'))
      ->save();
  }

}
