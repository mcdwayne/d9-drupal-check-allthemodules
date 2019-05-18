<?php

namespace Drupal\crm_core_user_sync\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity Reference unique reference constraint.
 *
 * Verifies that referenced entities referenced only once.
 *
 * @Constraint(
 *   id = "UniqueReference",
 *   label = @Translation("Unique Entity Reference reference", context = "Validation")
 * )
 */
class UniqueReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'A @entity_type with @field_name referencing entity with ID @id already exists.';

}
