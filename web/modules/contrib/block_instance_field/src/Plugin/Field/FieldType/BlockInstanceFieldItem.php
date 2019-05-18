<?php

namespace Drupal\block_instance_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'entity_reference_revisions' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 * - target_bundle: (optional): If set, restricts the entity bundles which may
 *   may be referenced. May be set to an single bundle, or to an array of
 *   allowed bundles.
 *
 * @FieldType(
 *   id = "block_instance_field",
 *   label = @Translation("Block with configuration"),
 *   description = @Translation("A block reference with configuration."),
 *   category = @Translation("Reference"),
 *   no_ui = FALSE,
 *   default_widget = "block_instance_configurator",
 *   default_formatter = "block_instance_field",
 * )
 */
class BlockInstanceFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = array(
      'target_id' => array(
        'description' => 'The ID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => 255,
      ),
      'configuration' => array(
        'description' => 'The configuration of the block.',
        'type' => 'text',
        'size' => 'big',
      )
    );

    $schema = array(
      'columns' => $columns,
      'indexes' => array(
        'target_id' => array('target_id'),
        'configuration' => array('configuration'),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Block ID'))
      ->setRequired(TRUE);

    $properties['configuration'] = DataDefinition::create('string')
      ->setLabel('Configuration')
      ->setDescription(new TranslatableMarkup('The configuration'))
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values['form']['subform'])) {
      $values['configuration'] = json_encode($values['form']['subform']);
      unset($values['form']['subform']);
    }

    parent::setValue($values, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->target_id !== NULL) {
      return FALSE;
    }
    return TRUE;
  }

}
