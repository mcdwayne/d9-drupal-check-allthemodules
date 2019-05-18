<?php

namespace Drupal\cg\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Content Guide.
 */
class CgSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cg_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cg.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cg.settings');

    $form['document_base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Document base path'),
      '#description' => $this->t('Base path of your Content Guide document. This can either be a full system path or relative to your Drupal installation.'),
      '#default_value' => $config->get('document_base_path'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('cg.settings');
    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
