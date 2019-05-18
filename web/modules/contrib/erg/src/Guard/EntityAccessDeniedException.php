<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an entity access denied exception.
 */
final class EntityAccessDeniedException extends \RuntimeException implements GuardExceptionInterface {

  /**
   * The entity reference that was guarded.
   *
   * @var \Drupal\erg\EntityReference
   */
  private $entityReference;

  /**
   * The operation on the referent access was checked to.
   *
   * @var string
   */
  private $referentOperation;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\erg\EntityReference $entityReference
   *   The entity reference that was guarded.
   * @param \Drupal\Core\Access\AccessResultInterface $accessResult
   *   The access result that led to this exception.
   * @param \Drupal\Core\Entity\EntityInterface $target
   *   The target entity to which access was denied.
   * @param string $operation
   *   The operation on the target access was checked to.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account whose access was denied.
   */
  public function __construct(EntityReference $entityReference, AccessResultInterface $accessResult, EntityInterface $target, string $operation, AccountInterface $account) {
    $this->entityReference = $entityReference;
    $this->referentOperation = $operation;
    $reason = $accessResult instanceof AccessResultReasonInterface ? $accessResult->getReason() : '*unknown*';
    $message = erg_format_message($entityReference,
        'Account @accountId ("@accountLabel") cannot "@operation" @targetTypeLabel @targetId ("@targetLabel") for reason "@reason", so @referentTypeLabel @referentId ("@referentLabel") cannot be referenced by @fieldLabel on @refereeTypeLabel @refereeId ("@refereeLabel").',
        [
          '@accountId' => $account->id() ?: '*unsaved*',
          '@accountLabel' => $account->getDisplayName(),
          '@operation' => $operation,
          '@reason' => $reason,
          '@targetId' => $target->id() ?: '*unsaved*',
          '@targetLabel' => $target->label(),
          '@targetTypeLabel' => $target->getEntityType()->getLabel(),
        ]);
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

  /**
   * Gets the entity operation on the referent access was checked to.
   *
   * @return string
   *   The entity operation.
   */
  public function getReferentOperation(): string {
    return $this->referentOperation;
  }

}
