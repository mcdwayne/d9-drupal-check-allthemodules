<?php

namespace Drupal\white_label_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic while settings forms.
 */
class WhileGeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'white_label_entity.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'white_label_entity_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('white_label_entity.settings');

    $form['entity_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity name'),
      '#default_value' => $config->get('entity_name'),
      '#maxlength' => 180,
    ];

    $form['entity_name_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity name plural'),
      '#default_value' => $config->get('entity_name_plural'),
      '#maxlength' => 180,
    ];

    $form['entity_type_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type name'),
      '#default_value' => $config->get('entity_type_name'),
      '#maxlength' => 180,
    ];

    $form['entity_type_name_plural'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type name plural'),
      '#default_value' => $config->get('entity_type_name_plural'),
      '#maxlength' => 180,
    ];

    $form['base_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#default_value' => $config->get('base_path'),
      '#maxlength' => 180,
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

    $this->config('white_label_entity.settings')
      ->set('entity_name', $form_state->getValue('entity_name'))
      ->set('entity_name_plural', $form_state->getValue('entity_name_plural'))
      ->set('entity_type_name', $form_state->getValue('entity_type_name'))
      ->set('entity_type_name_plural', $form_state->getValue('entity_type_name_plural'))
      ->set('base_path', $form_state->getValue('base_path'))
      ->save();

    // Necessary for entity info alters to take effect.
    drupal_flush_all_caches();
  }

}
