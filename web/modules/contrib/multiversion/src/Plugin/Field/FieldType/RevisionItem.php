<?php

namespace Drupal\multiversion\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *   id = "revision_token",
 *   label = @Translation("Revision token"),
 *   description = @Translation("Entity revision token."),
 *   no_ui = TRUE
 * )
 */
class RevisionItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Revision token'))
      ->setRequired(TRUE);

    $properties['new_edit'] = DataDefinition::create('boolean')
      ->setLabel(t('New edit flag'))
      ->setDescription(t('During replication this will be set to FALSE to ensure that the revision is saved as-is without generating a new token.'))
      ->setRequired(FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\multiversion\NewEdit');

    // Field item properties can't be lists for some good reason, so we define
    // it as a string here, but in reality it will be used as an array. It does
    // not matter much because this field is computed and will not be stored.
    $properties['revisions'] = DataDefinition::create('string')
      ->setLabel(t('A list of all known revisions of the entity.'))
      ->setDescription(t('During replication this will be populated with hashes (i.e. without the index prefix) from all known revisions of the entity.'))
      ->setRequired(FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\multiversion\Field\RevisionsProperty');

    $properties['is_stub'] = DataDefinition::create('boolean')
      ->setLabel(t('Is stub flag'))
      ->setDescription(t('This will be true just in case if the current revision is the children of a stub revision.'))
      ->setRequired(FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\multiversion\IsStub');

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
          'length' => 128,
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $this->setValue(
      [
        'value' => '0-00000000000000000000000000000000',
        'new_edit' => TRUE,
        'revisions' => [],
        // We don't have an opinion on the default value and will leave that up
        // to the logic in the property class to decide.
        // @see \Drupal\multiversion\IsStub::getValue()
        'is_stub' => NULL,
      ],
      $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $i = rand(0, 99);
    $hash = md5(rand());
    $token = "$i-$hash";

    return [
      'value' => $token,
      'new_edit' => TRUE,
      'revisions' => [$hash, md5(rand()), md5(rand())],
      'is_stub' => FALSE,
    ];
  }

}
