<?php

namespace Drupal\editor_note\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the EditorNote entity.
 *
 * @ContentEntityType(
 *   id = "editor_note",
 *   label = @Translation("EditorNote"),
 *   base_table = "editor_note",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "entity_id" = "entity_id",
 *     "revision_id" = "revision_id",
 *     "bundle" = "bundle",
 *     "field_machine_name" = "field_machine_name",
 *   },
 * )
 */
class EditorNote extends ContentEntityBase implements ContentEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('UID'))
      ->setDescription(t('The {users}.uid who authored the note.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId');

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The entity id note is attached to.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The entity revision id note is attached to, or NULL if the entity type is not versioned.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['note'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Editor note'))
      ->setDescription('Content of the note.')
      ->setTranslatable(TRUE);

    $fields['field_machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field machine name'))
      ->setSetting('max_length', 128)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the note was created, as a Unix timestamp.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the note was last edited, as a Unix timestamp.'));

    return $fields;
  }

}
