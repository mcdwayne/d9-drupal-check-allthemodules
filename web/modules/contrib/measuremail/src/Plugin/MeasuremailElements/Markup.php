<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;

/**
 * Provides a 'markup' element.
 *
 * @MeasuremailElements(
 *   id = "markup",
 *   label = @Translation("Markup"),
 *   description = @Translation("Provides a form element for to render text."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Markup extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'description' => '',
      'required' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#description' => t('Administrative label.'),
      '#default_value' => $this->configuration['label'],
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => t('Unique ID'),
      '#description' => t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => $this->configuration['id'],
      '#required' => TRUE,
    ];
    $form['description'] = [
      '#type' => 'text_format',
      '#title' => 'Body',
      '#format' => isset($this->configuration['description']['format']) ? $this->configuration['description']['format'] : 'full_html',
      '#default_value' => isset($this->configuration['description']['value']) ? $this->configuration['description']['value'] : '',
    ];
    $form['required'] = [
      '#type' => 'hidden',
      '#value' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['id'] = $form_state->getValue('id');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['required'] = $form_state->getValue('required');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $field_configuration = $this->getConfiguration()['data'];
    return [
      '#markup' => $field_configuration['description']['value'],
    ];
  }
}
