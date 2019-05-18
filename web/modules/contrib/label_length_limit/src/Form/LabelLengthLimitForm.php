<?php

namespace Drupal\label_length_limit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LabelLengthLimitForm.
 */
class LabelLengthLimitForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'label_length_limit.labellengthlimit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'label_length_limit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('label_length_limit.labellengthlimit');
    $form['label_max_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Enter Default Label Maximum Length'),
      '#description' => $this->t('Enter it between 128-255'),
      '#default_value' => $config->get('label_max_length'),
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

    $this->config('label_length_limit.labellengthlimit')
      ->set('label_max_length', $form_state->getValue('label_max_length'))
      ->save();
  }

}
