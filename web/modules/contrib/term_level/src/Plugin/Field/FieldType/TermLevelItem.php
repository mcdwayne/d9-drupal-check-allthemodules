<?php

namespace Drupal\term_level\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin for Term level field type.
 *
 * @FieldType(
 *   id = "term_level",
 *   label = @Translation("Term level"),
 *   description = @Translation("Term level field."),
 *   category = @Translation("Reference"),
 *   default_widget = "term_level_widget",
 *   default_formatter = "term_level_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class TermLevelItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'taxonomy_term',
      'levels' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['level'] = DataDefinition::create('integer')
      ->setLabel(t('Level'))
      ->setSetting('unsigned', TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['level'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $schema['indexes']['level'] = ['level'];
    return $schema;
  }

  /**
   * Extracts levels (level-key => level-label) out of the field settings.
   *
   * Level-label are not yet sanitized.
   *
   * @return array
   *   Extracted levels.
   */
  public static function extractLevels($value) {
    $levels = [];

    $list = explode("\n", $value);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');
    foreach ($list as $key => $value) {
      if (strpos($value, '|') !== FALSE) {
        list($level_key, $label) = explode('|', $value);
      }
      if (isset($level_key) && isset($level_key)) {
        $levels[$level_key] = $label;
      }
    }

    return $levels;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];
    $element['levels'] = [
      '#type' => 'textarea',
      '#title' => t('Levels'),
      '#default_value' => $this->getSetting('levels'),
      '#required' => TRUE,
      '#description' => t('Specify the term levels for this field. Enter one level per line, in the format level-key|level-label (level-key must be numeric).'),
      '#element_validate' => [[$this, 'validateLevels']],
      '#disabled' => $has_data,
    ];
    return $element;
  }

  /**
   * Validates levels value.
   */
  public function validateLevels($element, FormStateInterface $form_state) {
    $levels = self::extractLevels($element['#value']);
    if (count($levels) == 0) {
      $form_state->setError($element, $this->t('Please enter valid levels.'));
    }
    foreach ($levels as $key => $label) {
      if (!preg_match('/^\d+$/', $key)) {
        $form_state->setError($element, $this->t('The level key must be positive integer.'));
        break;
      }
    }
  }

}
