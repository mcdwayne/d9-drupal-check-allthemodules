<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Plugable\Access\HiddenTabAccessPluginManager;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Defines the access control handler for the hidden tab page entity type.
 */
class HiddenTabPageAccessControlHandler extends EntityAccessControlHandler {

  const SIMPLE_OP_PERM = [
    HiddenTabPageInterface::OP_UPDATE => HiddenTabPageInterface::PERMISSION_UPDATE,
    HiddenTabPageInterface::OP_DELETE => HiddenTabPageInterface::PERMISSION_DELETE,
    HiddenTabPageInterface::PERMISSION_UPDATE => HiddenTabPageInterface::PERMISSION_UPDATE,
    HiddenTabPageInterface::PERMISSION_DELETE => HiddenTabPageInterface::PERMISSION_DELETE,
  ];

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity,
                         $operation,
                         AccountInterface $account = NULL,
                         $return_as_object = FALSE,
                         EntityInterface $context_entity = NULL,
                         ParameterBag $bag = NULL) {
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
      $return = $return->orIf($this->checkAccess($entity, $operation, $account, $context_entity, $bag));
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
                                 ParameterBag $bag = NULL): AccessResult {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface $entity */
    /** @var \Drupal\hidden_tab\Plugable\Access\HiddenTabAccessInterface $plugin */

    // We do not want admin to see the page with wrong hash.
    if (AccessResult::allowedIfHasPermission($account, HiddenTabPageInterface::PERMISSION_ADMINISTER)
      ->isAllowed()) {
      switch ($operation) {
        case HiddenTabPageInterface::OP_VIEW_SECRET_URI:
          if (!$entity->enable()) {
            // TODO add to theme.
            \Drupal::messenger()
              ->addWarning(t('If you are visiting this entity, beware: this entity is not enabled'));
          }
      }
      return AccessResult::allowed();
    }

    if (isset(self::SIMPLE_OP_PERM[$operation])) {
      return AccessResult::allowedIfHasPermission($account, self::SIMPLE_OP_PERM[$operation]);
    }

    if (!$entity->enable()) {
      return AccessResult::forbidden('not enabled');
    }

    switch ($operation) {
      case HiddenTabPageInterface::PERMISSION_VIEW:
      case HiddenTabPageInterface::OP_VIEW:
        if ($account->hasPermission(HiddenTabPageInterface::PERMISSION_VIEW_ALL_TABS)) {
          return AccessResult::allowed();
        }
        if (!$account->hasPermission(HiddenTabPageInterface::PERMISSION_VIEW)) {
          return AccessResult::forbidden();
        }
        if ($entity->tabViewPermission() && !$account->hasPermission($entity->tabViewPermission())) {
          return AccessResult::forbidden();
        }
        if (!static::targetAllowed($entity, $account, $context_entity)) {
          return AccessResult::forbidden();
        }
        return AccessResult::allowed();

      case HiddenTabPageInterface::PERMISSION_VIEW_SECRET_URI:
        if (!$account->hasPermission(HiddenTabPageInterface::PERMISSION_VIEW_SECRET_URI)) {
          return AccessResult::forbidden();
        }
        if ($entity->secretUriViewPermission() && !$account->hasPermission($entity->secretUriViewPermission())) {
          return AccessResult::forbidden();
        }
        if (!static::targetAllowed($entity, $account, $context_entity)) {
          return AccessResult::forbidden();
        }
        if ($context_entity === NULL || $bag === NULL) {
          // We need a hash.
          return AccessResult::forbidden();
        }

        $access = AccessResult::neutral();
        foreach (HiddenTabAccessPluginManager::instance()
                   ->plugins() as $plugin) {
          $can = $plugin->canAccess(
            $context_entity,
            $account,
            $entity,
            $bag,
            HiddenTabPageInterface::PERMISSION_VIEW_SECRET_URI
          );
          if ($can->isForbidden()) {
            return $can;
          }
          $access = $can;
        }
        return $access;

      case HiddenTabPageInterface::PERMISSION_ADMINISTER:
        return AccessResult::allowedIfHasPermission($account, HiddenTabPageInterface::PERMISSION_ADMINISTER);

      default:
        return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      HiddenTabPageInterface::PERMISSION_ADMINISTER,
      HiddenTabPageInterface::PERMISSION_CREATE,
    ], 'OR');
  }

  private static function hasTarget(RefrencerEntityInterface $page): bool {
    return $page->targetEntityId() || $page->targetEntityId() || $page->targetEntityBundle();
  }

  private static function targetAllowed(RefrencerEntityInterface $page,
                                        AccountInterface $account,
                                        ?EntityInterface $context_entity): bool {
    if ($page->targetUserId() && $page->targetUserId() !== $account->id()) {
      return FALSE;
    }
    if (!static::hasTarget($page)) {
      return TRUE;
    }
    if (!$context_entity) {
      return FALSE;
    }
    if ($page->targetEntityType() && $context_entity->getEntityTypeId() !== $page->targetEntityType()) {
      return FALSE;
    }
    if ($page->targetEntityBundle() && $context_entity->bundle() !== $page->targetEntityBundle()) {
      return FALSE;
    }
    if ($page->targetEntityId() && $context_entity->id() !== $page->targetEntityId()) {
      return FALSE;
    }
    return TRUE;
  }

}
