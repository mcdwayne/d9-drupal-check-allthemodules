<?php

declare(strict_types = 1);

namespace Drupal\erg_test\Entity;

use Drupal\erg\Event;
use Drupal\erg\Field\FieldSettings;
use Drupal\erg\Guard\DeleteReferenceGuard;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides an entity with a reference that must be deleted with its target.
 *
 * @ContentEntityType(
 *   base_table = "erg_test_odrdreference",
 *   id = "erg_test_odrdreference",
 *   label = @Translation("OnDeleteReferentDeleteReference"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
final class OnDeleteReferentDeleteReference extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['users'] = BaseFieldDefinition::create('entity_reference')
      ->setReadOnly(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setLabel(t('Users'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setSetting('erg', FieldSettings::create()->withGuards([new DeleteReferenceGuard(Event::PRE_REFERENT_DELETE)]));

    return $fields;
  }

}
