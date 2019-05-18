<?php

namespace Drupal\entity_reference_validators\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class CircularReferenceConstraintValidator extends ConstraintValidator {

  /**
   * The selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var CircularReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }

    $entity = $value->getEntity();
    if (!isset($entity) || $entity->isNew()) {
      return;
    }

    foreach ($value as $delta => $item) {
      $id = $item->target_id;
      // '0' or NULL are considered valid empty references.
      if (empty($id)) {
        continue;
      }
      /* @var \Drupal\Core\Entity\FieldableEntityInterface $referenced_entity */
      $referenced_entity = $item->entity;

      if ($entity->id() === $referenced_entity->id() && $entity->getEntityTypeId() === $referenced_entity->getEntityTypeId()) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%type', $referenced_entity->getEntityTypeId())
          ->setParameter('%id', $referenced_entity->id())
          ->setInvalidValue($referenced_entity)
          ->atPath((string) $delta . '.target_id')
          ->addViolation();
      }
    }
  }

}
