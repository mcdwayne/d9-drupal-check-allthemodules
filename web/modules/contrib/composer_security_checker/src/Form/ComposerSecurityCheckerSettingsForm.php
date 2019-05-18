<?php

namespace Drupal\composer_security_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ComposerSecurityCheckerSettingsForm.
 *
 * @package Drupal\composer_security_checker\Form
 */
class ComposerSecurityCheckerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'composer_security_checker.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'composer_security_checker_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('composer_security_checker.settings');

    $form['composer_lock_file_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Composer lock file path'),
      '#default_value' => $config->get('composer_lock_file_path'),
      '#description' => $this->t('Path to composer.lock file.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $composer_lock_file_path = $form_state->getValue('composer_lock_file_path');

    if (!is_dir($composer_lock_file_path)) {
      $form_state->setErrorByName('composer_lock_file_path', $this->t('The directory entered does not exist.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('composer_security_checker.settings')
      ->set('composer_lock_file_path', $form_state->getValue('composer_lock_file_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
