<?php

namespace Drupal\content_parser\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Remote parser entity.
 *
 * @ingroup content_parser
 *
 * @ContentEntityType(
 *   id = "remote_parser",
 *   label = @Translation("Remote parser"),
 *   base_table = "remote_parser",
 *   handlers = {
 *     "views_data" = "Drupal\content_parser\Entity\RemoteParserViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   }
 * )
 */
class RemoteParser extends ContentEntityBase {


  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    $entity_type = $this->get('entity_type')->value;
    $entity_id = $this->get('entity_id')->value;

    $host = \Drupal::entityManager()
      ->getStorage($entity_type)
      ->load($entity_id);

    if ($host) {
      return $host;
    }

    return null;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote id'));
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'));
    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity id'));
    $fields['original_link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Original link'));
    $fields['original_link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Original link'));
    $fields['parser'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Parser')
      ->setSetting('target_type', 'content_parser');
    return $fields;
  }

}
