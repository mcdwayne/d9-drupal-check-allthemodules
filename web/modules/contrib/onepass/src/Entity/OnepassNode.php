<?php

namespace Drupal\onepass\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the EgoContent entity.
 *
 * @ContentEntityType(
 *   id = "onepass_node",
 *   label = @Translation("Onepass Node entity"),
 *   handlers = {
 *     "storage" = "Drupal\onepass\OnepassNodeStorage",
 *     "storage_schema" = "Drupal\onepass\OnepassNodeStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "onepass_node",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "nid",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class OnepassNode extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('Record id.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('Record UUID.'))
      ->setReadOnly(TRUE);

    $fields['nid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Node nid'))
      ->setDescription(t('Related node nid.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created time'))
      ->setDescription(t('Creation date Unix timestamp.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array('created' => REQUEST_TIME);
  }

}
