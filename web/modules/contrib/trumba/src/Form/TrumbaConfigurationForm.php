<?php

namespace Drupal\trumba\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TrumbaConfigurationForm.
 *
 * @package Drupal\trumba\Form
 */
class TrumbaConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'trumba.trumbaconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trumba_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('trumba.trumbaconfiguration');
    $form['default_web_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Web Name'),
      '#description' => $this->t('This is the default unique identifier for your account on Trumba.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('default_web_name'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('trumba.trumbaconfiguration')
      ->set('default_web_name', $form_state->getValue('default_web_name'))
      ->save();
  }

}
