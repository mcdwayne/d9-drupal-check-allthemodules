<?php

namespace Drupal\ssf\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the wordlist entity.
 *
 * @ContentEntityType(
 *   id = "ssf_wordlist",
 *   label = @Translation("SSF Wordlist"),
 *   base_table = "ssf_wordlist",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\user\UserViewsData",
 *     "storage_schema" = "Drupal\ssf\WordlistStorageSchema",
 *   },
 *   admin_permission = "administer site configuration",
 * )
 */
class Wordlist extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setSetting('max_length', 175)
      ->setRequired(TRUE)
      ->addConstraint('UniqueField', []);

    $fields['count_ham'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ham'));

    $fields['count_spam'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Spam'));

    return $fields;
  }

}
