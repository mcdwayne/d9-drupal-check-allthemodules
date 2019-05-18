<?php

namespace Drupal\flexiform\FormElement;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait for basic form element things.
 *
 * Because ContextAwarePluginBase is not split into traits.
 */
trait FormElementBaseTrait {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['settings'] = [
      '#type' => 'container',
    ];
    $form['settings']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label that will be displayed on the form.'),
      '#default_value' => !empty($this->configuration['label']) ? $this->configuration['label'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formValidate(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function formSubmit(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntities(array $form, FormStateInterface $form_state) {
  }

}
