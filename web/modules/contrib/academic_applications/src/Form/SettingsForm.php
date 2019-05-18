<?php

namespace Drupal\academic_applications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\academic_applications\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'academic_applications.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('academic_applications.settings');
    $form['ghostscript_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GhostScript Path'),
      '#description' => $this->t('Enter the path to the GhostScript executable.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('ghostscript_path'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('academic_applications.settings')
      ->set('ghostscript_path', $form_state->getValue('ghostscript_path'))
      ->save();
  }

}
