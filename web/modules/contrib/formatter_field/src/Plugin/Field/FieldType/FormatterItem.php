<?php

namespace Drupal\formatter_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Defines the 'formatter' field type.
 *
 * @FieldType(
 *   id = "formatter_field_formatter",
 *   label = @Translation("Formatter"),
 *   category = @Translation("General"),
 *   default_widget = "formatter_field_formatter",
 *   default_formatter = "formatter_field_formatter"
 * )
 */
class FormatterItem extends FieldItemBase {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    // @todo Inject dependencies once core supports it.
    // @see https://www.drupal.org/project/drupal/issues/2053415
    $this->fieldManager = \Drupal::service('entity_field.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['field' => NULL] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $own_definition = $this->getFieldDefinition();
    // Fetch all other fields attached to this bundle.
    $definitions = $this->fieldManager->getFieldDefinitions(
      $own_definition->getTargetEntityTypeId(),
      $own_definition->getTargetBundle()
    );
    $own_name = $this->getFieldDefinition()->getName();

    $options = [];
    foreach ($definitions as $field_name => $definition) {
      if ($definition instanceof FieldConfigInterface && $field_name != $own_name) {
        $options[$field_name] = $definition->label();
      }
    }

    $element['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field to be formatted'),
      '#default_value' => $this->getSetting('field'),
      '#required' => TRUE,
      '#options' => $options,
      '#description' => $this->t('The field to be formatted using the settings in this field.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('type')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Formatter'));

    $properties['settings'] = DataDefinition::create('any')
      ->setLabel(t('Formatter settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema['columns'] = [
      'type' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Formatter type',
        'length' => 64,
      ],
      'settings' => [
        'type' => 'text',
        'size' => 'big',
        'not null' => FALSE,
      ],
    ];

    return $schema;
  }

}
