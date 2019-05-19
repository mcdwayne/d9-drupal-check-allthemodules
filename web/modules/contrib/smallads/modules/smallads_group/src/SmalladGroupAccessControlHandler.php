<?php

namespace Drupal\smallads_group;

use Drupal\group\Access\GroupAccessResult;
use Drupal\group\Context\GroupRouteContext;
use Drupal\group\Entity\GroupContentType;
use Drupal\smallads\Entity\SmalladInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an access controller for the Smallad entity which is forced into a group
 */
class SmalladGroupAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * @var \Drupal\group\Context\GroupRouteContext
   */
  protected $groupContext;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a NodeAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param \Drupal\group\Context\GroupRouteContext $group_context
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(EntityTypeInterface $entity_type, GroupRouteContext $group_context, EntityTypeManager $entity_type_manager) {
    parent::__construct($entity_type);
    $this->groupContext = $group_context;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('group.group_route_context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $smallAd, $operation, AccountInterface $account) {
    // Owners can do anything with their own smallads.
    if ($smallAd->getOwnerId() == $account->id()) {
      return AccessResult::allowed()->cachePerUser()->addCacheableDependency($smallAd);
    }
    if ($account->hasPermission('configure smallads')) {
      return AccessResult::allowed()->cachePerUser()->cachePerPermissions();
    }
    // At this point we don't care what is the Group from the path, we need the
    // group that this smallad belongs to.
    $group_content_types = GroupContentType::loadByContentPluginId('smallad:'.$smallAd->bundle());
    if (empty($group_content_types)) {
      return AccessResult::neutral();
    }
    $group_contents = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $smallAd->id(),
      ]);
    // If the smallad does not belong to any group, we have nothing to say.
    if (empty($group_contents)) {
      return AccessResult::neutral();
    }

    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group = $group_content->getGroup();
      if ($group->hasPermission('manage-smallads', $account)) {
        return AccessResult::allowed();
      }
      if ($operation == 'view') {
        switch ($smallAd->scope->value) {
          case SmalladInterface::SCOPE_PUBLIC:
            //NB this doesn't need to load the group
            return AccessResult::allowed();
          case SmalladInterface::SCOPE_NETWORK:
            //this doesn't mean anything yet
          case SmalladInterface::SCOPE_SITE:
            return AccessResult::allowedIfHasPermission($account, 'access content');
          case SmalladInterface::SCOPE_GROUP:
            if ($group->hasPermission('create-edit-delete own smallads', $account)) {
              return AccessResult::allowed();
            }
          case SmalladInterface::SCOPE_PRIVATE:
            // This case is already handled because only the owner and admin can access it.
          default:
            throw new \Exception('Invalid smallad scope');
        }
      }
      // We don't deal with all the operations here because they are only
      // possible by owner and admin
    }
    return AccessResult::forbidden()->addCacheableDependency($smallAd);
  }


  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($group = $this->groupContext->getGroupFromRoute()) {
      // @todo Not sure how to cache this.
      return GroupAccessResult::allowedIfHasGroupPermission($group, $account, 'create-edit-delete own smallads');
    }
    return AccessResult::forbidden()->cachePerUser();
  }

}
