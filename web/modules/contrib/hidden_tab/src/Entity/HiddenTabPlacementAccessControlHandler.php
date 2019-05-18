<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Utility;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Defines the access control handler for the hidden tab placement entity type.
 */
class HiddenTabPlacementAccessControlHandler extends EntityAccessControlHandler {

  /**
   *  One of the most dangerous permissions (for placements) as placements
   *  give access TO ANY VIEW/BLOCK and whatever on the site.
   */
  const PERMISSION_ADMINISTER = Utility::ADMIN_PERMISSION;

  const OP_ADMINISTER = self::PERMISSION_ADMINISTER;

  const PERMISSION_UPDATE = 'update hidden tab placement';

  const PERMISSION_DELETE = 'delete hidden tab placement';

  const PERMISSION_CREATE = 'create hidden tab placement';

  const PERMISSION_VIEW = 'view hidden tab placement';

  const OP_UPDATE = 'update';

  const OP_DELETE = 'delete';

  const OP_VIEW = 'view';

  const SIMPLE_OP_PERM = [
    self::OP_UPDATE => self::PERMISSION_UPDATE,
    self::OP_DELETE => self::PERMISSION_DELETE,
  ];

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity,
                         $operation,
                         AccountInterface $account = NULL,
                         $return_as_object = FALSE,
                         EntityInterface $context_entity = NULL,
                         ParameterBag $query = NULL) {
    $account = $this->prepareUser($account);
    $langcode = $entity->language()->getId();

    if ($operation === 'view label' && $this->viewLabelOperation == FALSE) {
      $operation = 'view';
    }

    $cid = $entity->uuid() ?: $entity->getEntityTypeId() . ':' . $entity->id();

    if (($return = $this->getCache($cid, $operation, $langcode, $account)) !== NULL) {
      return $return_as_object ? $return : $return->isAllowed();
    }

    $access = array_merge(
      $this->moduleHandler()->invokeAll('entity_access', [
        $entity,
        $operation,
        $account,
      ]),
      $this->moduleHandler()
        ->invokeAll($entity->getEntityTypeId() . '_access', [
          $entity,
          $operation,
          $account,
        ])
    );

    $return = $this->processAccessHookResults($access);

    if (!$return->isForbidden()) {
      $return = $return->orIf($this->checkAccess($entity, $operation, $account, $context_entity, $query));
    }
    $result = $this->setCache($return, $cid, $operation, $langcode, $account);
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity,
                                 $operation,
                                 AccountInterface $account,
                                 EntityInterface $context_entity = NULL,
                                 ParameterBag $query = NULL): AccessResult {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface $entity */
    $admin = AccessResult::allowedIfHasPermission($account, self::PERMISSION_ADMINISTER);
    if ($admin->isAllowed()) {
      return $admin;
    }
    if (isset(self::SIMPLE_OP_PERM[$operation])) {
      return AccessResult::allowedIfHasPermission($account, self::SIMPLE_OP_PERM[$operation]);
    }

    if ($operation !== self::OP_VIEW) {
      return AccessResult::forbidden();
    }

    if ($entity->targetUserId() && $entity->targetUserId() !== $account->id()) {
      return AccessResult::forbidden();
    }
    if (($entity->targetEntityId() || $entity->targetEntityType() || $entity->targetEntityBundle()) && !$context_entity) {
      return AccessResult::forbidden();
    }
    if ($entity->targetEntityId() && $entity->targetEntityId() !== $context_entity->id()
      || $entity->targetEntityType() && $entity->targetEntityType() !== $context_entity->getEntityTypeId()
      || $entity->targetEntityBundle() && $context_entity->bundle() !== $entity->targetEntityBundle()) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowedIf($entity->isEnabled());
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      static::PERMISSION_ADMINISTER,
      static::PERMISSION_CREATE,
    ], 'OR');
  }

}
