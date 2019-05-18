<?php

namespace Drupal\commerce_inventory\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type.
 */
class UniquePurchasableEntityValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      $this->context->addViolation($constraint->message['empty']);
      return;
    }
    /** @var \Drupal\commerce_inventory\Entity\InventoryItem $entity */
    $entity = $items->getEntity();
    $purchasable_entity = $entity->getPurchasableEntity();
    $location = $entity->getLocation();

    // Need a valid purchasable entity.
    if (is_null($purchasable_entity)) {
      $this->context->addViolation($constraint->message['required_purchasable_entity']);
      return;
    }

    // Need a valid location.
    if (is_null($location)) {
      $this->context->addViolation($constraint->message['required_location']);
      return;
    }

    /** @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_inventory_item');
    $item_id = $storage->getItemId($location->id(), $purchasable_entity->getEntityTypeId(), $purchasable_entity->id());

    if (!is_null($item_id) && $item_id !== $entity->id()) {
      $params['%purchasable_entity'] = $purchasable_entity->label();
      $params['%location'] = $location->label();
      $this->context->addViolation($constraint->message['exists_context'], $params);
    }
  }

}
