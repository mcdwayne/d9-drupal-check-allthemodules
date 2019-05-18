<?php

declare(strict_types = 1);

namespace Drupal\erg_test\Entity;

use Drupal\erg\Event;
use Drupal\erg\Field\FieldSettings;
use Drupal\erg\Guard\ReferentAccessCheckGuard;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides an entity that can only reference entities the user has access to.
 *
 * @ContentEntityType(
 *   base_table = "erg_test_opvrrac",
 *   id = "erg_test_opvrrac",
 *   label = @Translation("OnPreValidateRefereeReferentAccessCheck"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
final class OnPreValidateRefereeReferentAccessCheck extends ContentEntityBase {

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
      ->setSetting('erg', FieldSettings::create()->withGuards([new ReferentAccessCheckGuard(Event::REFEREE_VALIDATE, 'view')]));

    return $fields;
  }

}
