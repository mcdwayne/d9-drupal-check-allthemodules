<?php

namespace Drupal\simplemeta\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\simplemeta\Form
 *
 * @ingroup simplemeta
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simplemeta_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simplemeta.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simplemeta.settings');

    $form['form_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Add Meta Tags Form'),
      '#description' => t('If enabled, form will appear on pages'),
      '#default_value' => $config->get('form_enable'),
      '#return_value' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simplemeta.settings');

    $config
      ->set('form_enable', $form_state->getValue('form_enable'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
