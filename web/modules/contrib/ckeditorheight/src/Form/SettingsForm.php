<?php

namespace Drupal\ckeditorheight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ckeditorheight.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditorheight_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditorheight.settings');
    $form['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Offset'),
      '#description' => $this->t('The offset, aka height for zero lines.'),
      '#default_value' => $config->get('offset'),
    ];
    $form['line_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Line height'),
      '#description' => $this->t('The line height.'),
      '#default_value' => $config->get('line_height'),
    ];
    $form['unit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unit'),
      '#description' => $this->t('The unit for offset and line height.'),
      '#maxlength' => 4,
      '#size' => 4,
      '#default_value' => $config->get('unit'),
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

    $this->config('ckeditorheight.settings')
      ->set('offset', $form_state->getValue('offset'))
      ->set('line_height', $form_state->getValue('line_height'))
      ->set('unit', $form_state->getValue('unit'))
      ->save();
  }

}
