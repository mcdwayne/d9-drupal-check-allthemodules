<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides scaffolding for guards checking entity access.
 */
abstract class EntityAccessCheckGuardBase implements GuardInterface {

  /**
   * The name of a field that contains a reference to an account entity.
   *
   * The name of any entity reference field on the referee that refers an entity
   * that implements \Drupal\Core\Sessoin\AccountInterface is valid. Use NULL to
   * default to the currently logged-in user.
   *
   * @var string|null
   */
  private $accountReferenceFieldName;

  /**
   * The event to guard.
   *
   * @var string
   */
  private $event;

  /**
   * The operation on the referent to check access for.
   *
   * @var string
   */
  private $operation;

  /**
   * Constructs a new instance.
   *
   * @param string $event
   *   The event to guard.
   * @param string $operation
   *   The operation to check access for.
   * @param string|null $accountReferenceFieldName
   *   The name of the referee field that references an entity that implements
   *   \Drupal\Core\Session\AccountInterface and should be used as the subject
   *   for access control. Use NULL for the currently logged-in user.
   */
  public function __construct(
    string $event,
    string $operation,
    string $accountReferenceFieldName = NULL
  ) {
    $this->accountReferenceFieldName = $accountReferenceFieldName;
    $this->event = $event;
    $this->operation = $operation;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent(): string {
    return $this->event;
  }

  /**
   * Gets the target entity to check access to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The target entity.
   */
  abstract protected function getAccessTarget(EntityReference $entityReference
  ): ?EntityInterface;

  /**
   * {@inheritdoc}
   */
  public function guardReference(EntityReference $entityReference) {
    $target = $this->getAccessTarget($entityReference);
    $account = $this->accountReferenceFieldName ? $entityReference->getReferee()->{$this->accountReferenceFieldName}->entity : \Drupal::currentUser();
    $access = $target->access($this->operation, $account, TRUE);
    if (!$access->isAllowed()) {
      throw new EntityAccessDeniedException($entityReference, $access, $target,
        $this->operation, $account);
    }
  }

}
