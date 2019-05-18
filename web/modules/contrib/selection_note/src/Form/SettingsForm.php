<?php

namespace Drupal\selection_note\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure global selection note settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'selection_note_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'selection_note.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    if (!$this->config('selection_note.settings')->get('content_type')) {
      drupal_set_message(t('No content type has been selected.'), 'error');
    }
    if (!$this->config('selection_note.settings')->get('relation_type')) {
      drupal_set_message(t('No relation type has been selected.'), 'error');
    }
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    $content_options = [];
    foreach($content_types as $id => $content_type) {
      $content_options[$content_type->id()] = $content_type->label();
    }
    $form['content_type'] = [
      '#title' => t('Content type'),
      '#description' => t('Content type where to save notes.'),
      '#type' => 'select',
      '#options' => $content_options,
      '#default_value' => $this->config('selection_note.settings')->get('content_type') ?: '',
      '#empty_option' => t('None'),
    ];

    $relation_types = \Drupal::service('entity.manager')->getStorage('relation_type')->loadMultiple();
    $relation_options = [];
    foreach($relation_types as $id => $relation_type) {
      $relation_options[$relation_type->id()] = $relation_type->label();
    }
    $form['relation_type'] = [
      '#title' => t('Relation type'),
      '#description' => t('Relation type to link nodes.'),
      '#type' => 'select',
      '#options' => $relation_options,
      '#default_value' => $this->config('selection_note.settings')->get('relation_type') ?: '',
      '#empty_option' => t('None'),
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
    $this->config('selection_note.settings')->set('content_type', $form_state->getValue('content_type'))->save();
    $this->config('selection_note.settings')->set('relation_type', $form_state->getValue('relation_type'))->save();
    parent::submitForm($form, $form_state);
  }

}
