<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;

/**
 * Provides a 'checkbox' element.
 *
 * @MeasuremailElements(
 *   id = "checkbox",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox",
 *   label = @Translation("Checkbox"),
 *   description = @Translation("Provides a form element for a single checkbox."),
 *   category = @Translation("Basic elements"),
 * )
 */
class CheckBox extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'default_value' => '',
      'required' => FALSE,
      'use_label' => TRUE,
      'alt_label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#default_value' => $this->configuration['label'],
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'textfield',
      '#title' => t('Measuremail field ID'),
      '#description' => t('Same ID as on Measuremail'),
      '#default_value' => $this->configuration['id'],
      '#required' => TRUE,
    ];
    $form['default_value'] = [
      '#type' => 'checkbox',
      '#title' => t('Selected by default'),
      '#default_value' => $this->configuration['default_value'],
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->configuration['required'],
    ];
    $form['use_label'] = [
      '#type' => 'checkbox',
      '#title' => t('Use element label as checkbox label'),
      '#default_value' => $this->configuration['use_label'],
    ];
    $form['alt_label'] = [
      '#type' => 'textfield',
      '#title' => t('Alternative label for Checkbox value'),
      '#default_value' => $this->configuration['alt_label'],
      '#states' => [
        'visible' => [
          ':input[name="data[use_label]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="data[use_label]"]' => ['checked' => FALSE],
        ],
      ],
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
    $this->configuration['default_value'] = $form_state->getValue('default_value');
    $this->configuration['required'] = $form_state->getValue('required');
    $this->configuration['use_label'] = $form_state->getValue('use_label');
    $this->configuration['alt_label'] = $form_state->getValue('alt_label');
  }

  public function render() {
    $field_configuration = $this->getConfiguration()['data'];

    if (!$field_configuration['use_label']) {
      // If we use a custom label, we'll add the checkboxes type to this field.
      $return = [
        '#type' => 'checkboxes',
        '#title' => t($field_configuration['label']),
        '#default_value' => ($field_configuration['default_value']) ? [1] : [],
        '#required' => ($field_configuration['required']) ? TRUE : FALSE,
        '#options' => [1 => $field_configuration['alt_label']],
      ];
    }
    else {
      $return = [
        '#type' => $this->getPluginId(),
        '#title' => t($field_configuration['label']),
        '#default_value' => $field_configuration['default_value'],
        '#required' => ($field_configuration['required']) ? TRUE : FALSE,
      ];
    }

    return $return;

  }
}
