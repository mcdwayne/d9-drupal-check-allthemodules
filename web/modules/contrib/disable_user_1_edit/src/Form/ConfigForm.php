<?php

namespace Drupal\disable_user_1_edit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 *
 * @package Drupal\disable_user_1_edit\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'disable_user_1_edit.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('disable_user_1_edit.settings');
    $form['disabled'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('disabled'),
      '#title' => $this->t('Disable restriction'),
      '#description' => $this->t('Make user 1 editable again. That is to say: Disable disable user 1 edit.'),
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

    $this->config('disable_user_1_edit.settings')
      ->set('disabled', $form_state->getValue('disabled'))
      ->save();
  }

}
