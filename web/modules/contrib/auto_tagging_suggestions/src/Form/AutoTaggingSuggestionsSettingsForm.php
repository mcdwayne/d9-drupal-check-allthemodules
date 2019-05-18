<?php

namespace Drupal\auto_tagging_suggestions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Build AutoTagging Suggestions settings form.
 */
class AutoTaggingSuggestionsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'auto_tagging_suggestions_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['auto_tagging_suggestions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auto_tagging_suggestions.settings');

    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    // Fieldset for grouping general settings fields.
    $form['auto_tagging_suggestions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configure Auto Tagging Suggestions'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['auto_tagging_suggestions']['content_types'] = [
      '#type' => 'select',
      '#multiple' => TRUE, 
      '#title' => $this->t('Select content types'),
      '#description' => $this->t('Select the content types for which you want to have autotagging suggestions.'),
      '#options' => $contentTypesList,
      '#default_value' => $config->get('autoTaggingtype'),
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
    $config = $this->config('auto_tagging_suggestions.settings');
    $config
      ->set('autoTaggingtype', $form_state->getValue('content_types'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
