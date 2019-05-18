<?php

namespace Drupal\entity_access_by_role\Plugin\Field\FieldType;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Plugin implementation of the 'role_access' field type.
 *
 * @FieldType(
 *   id = "role_access",
 *   label = @Translation("Role Access"),
 *   module = "entity_access_by_role",
 *   description = @Translation("Access Control Field"),
 *   default_widget = "role_access_widget",
 *   default_formatter = "role_access_formatter",
 * )
 */
class RoleAccessItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->addConstraint('Length', ['max' => 255])
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {

    return [
        'allowed_values' => [],
        "always_allowed" => [],
        'allowed_values_function' => 'entity_access_by_role_get_always_allowed_roles',
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {

    if (Unicode::strlen($option) > 255) {
      return t('Allowed values list: each key must be a string at most 255 characters long.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function castAllowedValue($value) {

    return (string) $value;
  }

  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    // This is ok here.
    $element['always_allowed'] = [
      "#type" => "checkboxes",
      "#title" => $this->t("Always allowed"),
      "#multiple" => TRUE,
      '#options' => entity_access_by_role_roles_without_bypass_access(),
      "#description" => $this->t("Select roles that should always be granted access to the content type this field belongs to.<br> The roles selected here will not appear on field when creating/editing content."),
      '#default_value' => $this->getSetting('always_allowed'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {

    $description = '<p>' . t('The values of this field are populated by the roles available on your site and will be updated automatically.</p>');
    return $description;
  }

}
