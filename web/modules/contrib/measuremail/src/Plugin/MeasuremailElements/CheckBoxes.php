<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;

/**
 * Provides a 'checkboxes' element.
 *
 * @MeasuremailElements(
 *   id = "checkboxes",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes",
 *   label = @Translation("Checkboxes"),
 *   description = @Translation("Provides a form element for a single checkboxes."),
 *   category = @Translation("Basic elements"),
 * )
 */
class CheckBoxes extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'default_value' => '',
      'required' => FALSE,
      'options' => [],
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
      '#type' => 'textfield',
      '#title' => t('Default value'),
      '#default_value' => $this->configuration['default_value'],
    ];
    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->configuration['required'],
    ];
    $form['options'] = [
      '#type' => 'textarea',
      '#title' => t('Options'),
      '#description' => t('Please insert one option per line with a key|value format.'),
      '#default_value' => $this->configuration['options'],
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
    $this->configuration['options'] = $form_state->getValue('options');
  }

  public function render() {
    $field_configuration = $this->getConfiguration()['data'];

    $options = preg_split('/\r\n|\r|\n/', $field_configuration['options']);
    foreach ($options as $option) {
      $keyvalue = explode('|', $option);
      $options_array[$keyvalue[0]] = isset($keyvalue[1]) ? t($keyvalue[1]) : t($keyvalue[0]);
    }

    return [
      '#type' => 'checkboxes',
      '#title' => t($field_configuration['label']),
      '#default_value' => [$field_configuration['default_value']],
      '#required' => ($field_configuration['required']) ? TRUE : FALSE,
      '#options' => $options_array,
    ];
  }
}
