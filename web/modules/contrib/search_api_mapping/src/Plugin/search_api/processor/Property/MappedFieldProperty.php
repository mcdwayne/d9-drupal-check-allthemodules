<?php

namespace Drupal\search_api_mapping\Plugin\search_api\processor\Property;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;
use Drupal\search_api\Utility\Utility;

/**
 * Defines an "mapped field" property.
 *
 * @see \Drupal\search_api_mapping\Plugin\search_api\processor\MappedField
 */
class MappedFieldProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field' => '',
      'mapping' => '',
      'with_value' => '',
      'without_value' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $index = $field->getIndex();
    $configuration = $field->getConfiguration();

    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';
    $form['#tree'] = TRUE;

    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Mapping field'),
      '#options' => [],
      '#default_value' => $configuration['field'],
      '#required' => TRUE,
    ];

    $fields = $index->getFields();

    $field_options = [];
    foreach ($fields as $field) {
      $combined_id = Utility::createCombinedId($field->getDatasourceId(), $field->getPropertyPath());
      list($datasource_id, $name) = Utility::splitCombinedId($combined_id);

      if (!$datasource_id && $name == 'mapped_field') {
        continue;
      }

      $field_options[$combined_id] = Html::escape($field->getLabel());
    }

    asort($field_options, SORT_NATURAL);
    $form['field']['#options'] = $field_options;

    $form['mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping'),
      '#description' => 'Use "orignal_value|new_value". Only one mapping per line.',
      '#default_value' => $configuration['mapping'],
    ];

    $form['with_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target value for non empty source values.'),
      '#description' => 'This target value is used for all source values which are not catched by the mapping and which are not empty.',
      '#default_value' => $configuration['with_value'],
    ];

    $form['without_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target value for empty source values.'),
      '#description' => 'This target value is used for all source values which are not catched by the mapping and which are empty.',
      '#default_value' => $configuration['without_value'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(FieldInterface $field, array &$form, FormStateInterface $form_state) {
    $values = [
      'field' => $form_state->getValue('field'),
      'mapping' => $form_state->getValue('mapping'),
      'with_value' => $form_state->getValue('with_value'),
      'without_value' => $form_state->getValue('without_value'),
    ];

    $field->setConfiguration($values);
  }

}
