<?php

namespace Drupal\annotation_store\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\annotation_store\Form
 */
class AnnotationStoreConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'annotation_store.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'annotation_store_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('annotation_store.settings');
    $form['annotation_store_date_format'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Date Format'),
      '#default_value' => $config->get('annotation_store_date_format'),
    );
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
    $this->config('annotation_store.settings')
      ->set('annotation_store_date_format', $form_state->getValue('annotation_store_date_format'))
      ->save();
  }

}
