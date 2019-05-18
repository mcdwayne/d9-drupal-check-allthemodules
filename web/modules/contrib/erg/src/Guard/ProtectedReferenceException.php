<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;

/**
 * Defines an exception that indicates a reference is protected.
 */
final class ProtectedReferenceException extends \RuntimeException implements GuardExceptionInterface {

  /**
   * The entity reference that was guarded.
   *
   * @var \Drupal\erg\EntityReference
   */
  private $entityReference;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\erg\EntityReference $entityReference
   *   The entity reference that was guarded.
   */
  public function __construct(EntityReference $entityReference) {
    $this->entityReference = $entityReference;
    $message = erg_format_message($entityReference, 'Cannot perform this operation on @referentTypeLabel @referentId ("@referentLabel"), because @fieldLabel on @refereeTypeLabel @refereeId ("@refereeLabel") references it.');
    parent::__construct($message);
  }

  /**
   * Gets the entity reference the exception is thrown for.
   *
   * @return \Drupal\erg\EntityReference
   *   The entity reference.
   */
  public function getEntityReference(): EntityReference {
    return $this->entityReference;
  }

}
