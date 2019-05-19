<?php

namespace Drupal\sfs\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the sfs hostname entity.
 *
 * @ContentEntityType(
 *   id = "sfs_hostname",
 *   label = @Translation("SFS Hostname"),
 *   base_table = "sfs_hostname",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "hostname",
 *   },
 *   handlers = {
 *     "storage_schema" = "Drupal\sfs\SfsHostnameStorageSchema",
 *   },
 *   admin_permission = "administer sfs",
 * )
 */
class SfsHostname extends ContentEntityBase implements ContentEntityInterface {

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return array|\Drupal\Core\Field\FieldDefinitionInterface[]|mixed
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
    ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host name'));
	  
    $fields['uid'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('User ID')); //index

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Entity ID'));

    $fields['entity_type'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Entity type'));

    $fields['created'] = BaseFieldDefinition::create('created')
    ->setLabel(t('Creation date'));

    return $fields;
  }
}
