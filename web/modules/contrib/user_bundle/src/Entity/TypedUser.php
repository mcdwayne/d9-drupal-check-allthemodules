<?php

namespace Drupal\user_bundle\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\Entity\User;

/**
 * Defines a bundle-compatible user entity class.
 */
class TypedUser extends User {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['type']->setLabel(t('Account type'))
      ->setDescription(t('The user account type.'));

    return $fields;
  }

}
