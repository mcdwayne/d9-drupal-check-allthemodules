<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseGeneric.
 */

namespace Drupal\entity_base\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;


/**
 * Implements enhancements to the Entity class.
 *
 * @ingroup entity_api
 */
abstract class EntityBaseGeneric extends EntityBaseSimple implements EntityBaseGenericInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $this->bundle());
    return $this;
  }

}
