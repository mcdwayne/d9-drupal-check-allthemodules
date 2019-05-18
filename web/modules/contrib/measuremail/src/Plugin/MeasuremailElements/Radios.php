<?php

namespace Drupal\measuremail\Plugin\MeasuremailElements;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\ConfigurableMeasuremailElementBase;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Provides a 'radios' element.
 *
 * @MeasuremailElements(
 *   id = "radios",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios",
 *   label = @Translation("Radios"),
 *   description = @Translation("Provides a form element for a set of radio buttons."),
 *   category = @Translation("Basic elements"),
 * )
 */
class Radios extends ConfigurableMeasuremailElementBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label' => '',
      'id' => '',
      'default_value' => '',
      'required' => FALSE,
      'options' => '',
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

    $form['options'] = [
      '#type' => 'textarea',
      '#title' => t('Options'),
      '#description' => t('The possible values this field can contain. Enter one value per line, in the format key|label.') . '\n' . t('The key is the stored value. The label will be used in displayed values and edit forms.') . '\n' . t('The label is optional: if a line contains a single string, it will be used as key and label.'),
      '#default_value' => $this->configuration['options'],
      '#required' => TRUE,
    ];

    $form['required'] = [
      '#type' => 'checkbox',
      '#title' => t('Required'),
      '#default_value' => $this->configuration['required'],
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $this->configuration['label'] = $form_state->getValue('label');
    $this->configuration['id'] = $form_state->getValue('id');
    $this->configuration['default_value'] = $form_state->getValue('default_value');
    $this->configuration['required'] = $form_state->getValue('required');

    $this->configuration['options'] = $form_state->getValue('options');

  }

  public function render() {
    $field_configuration = $this->getConfiguration()['data'];
    return [
      '#type' => $this->getPluginId(),
      '#title' => t($field_configuration['label']),
      '#default_value' => $field_configuration['default_value'],
      '#required' => ($field_configuration['required']) ? TRUE : FALSE,
      '#options' => $this->extractAllowedValues($field_configuration['options']),
    ];
  }

  /**
   * Extracts the allowed values array for the options field.
   *
   * @param string $string
   *   The raw string to extract values from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   *
   */
  private function extractAllowedValues($string) {
    $values = [];

    $list = explode("\r\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      // Check for an explicit key.
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
      }
      // Otherwise see if we can use the value as the key.
      else {
        if (Unicode::strlen($text) < 255) {
          $key = $value = $text;
        }
        else {
          continue;
        }
      }
      $values[$key] = $value;
    }

    return $values;
  }
}
