<?php

namespace Drupal\workflow_participants\Access;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Access\AccessException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access checker for workflow participants manager form.
 */
class WorkflowParticipantsAccessChecker implements AccessInterface {

  /**
   * The workflow participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Construct the access checker.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ModerationInformationInterface $moderation_information, EntityTypeManagerInterface $entity_type_manager) {
    $this->moderationInfo = $moderation_information;
    $this->participantStorage = $entity_type_manager->getStorage('workflow_participants');
  }

  /**
   * Verify access for the workflow participants manager form.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $entity = $this->loadEntity($route, $route_match);

    // If this entity cannot be moderated, deny access.
    if (!$this->moderationInfo->isModeratedEntity($entity)) {
      return AccessResultForbidden::forbidden()->addCacheableDependency($entity);
    }

    if ($account->hasPermission('manage workflow participants')) {
      return AccessResultAllowed::allowed()->addCacheableDependency($entity);
    }

    if ($entity instanceof EntityOwnerInterface) {
      // Allowed if user is a participant on the current entity. Further access
      // for editors and reviewers is controlled at the form level.
      $participants = $this->participantStorage->loadForModeratedEntity($entity);
      if ($participants->isEditor($account) || $participants->isReviewer($account)) {
        return AccessResult::allowed()->addCacheableDependency($entity)->addCacheableDependency($participants);
      }

      // Allowed if user is the author and has appropriate permission.
      return AccessResult::allowedIfHasPermission($account, 'manage own workflow participants')->andIf(AccessResult::allowedIf($entity->getOwnerId() == $account->id()))->addCacheableDependency($entity);
    }

    $access = AccessResult::forbidden()->addCacheableDependency($entity);
    if (isset($participants)) {
      $access->addCacheableDependency($participants);
    }
    return $access;
  }

  /**
   * Verify entity access for participants.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param string $operation
   *   The entity operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The logged in account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function hasEntityAccess(ContentEntityInterface $entity, $operation, AccountInterface $account) {
    $participants = $this->participantStorage->loadForModeratedEntity($entity);
    if (!$participants->id() || (empty($participants->getEditorIds()) && empty($participants->getReviewerIds()))) {
      // No participants.
      $access = AccessResult::neutral();
      if ($participants->id()) {
        $access->addCacheableDependency($participants);
      }
      return $access;
    }

    if ($operation === 'view' && $entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
      // Read operation, editors and reviewers can view.
      return AccessResult::allowedIf($participants->isReviewer($account) || $participants->isEditor($account))->addCacheableDependency($participants);
    }

    if ($operation === 'update') {
      // Only editors can update.
      return AccessResult::allowedIf($participants->isEditor($account))->addCacheableDependency($participants);
    }

    // Default to neutral.
    return AccessResult::neutral()->addCacheableDependency($participants);
  }

  /**
   * Returns the entity from the route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   *
   * @throws \Drupal\Core\Access\AccessException
   *   An exception is thrown if the entity couldn't be loaded.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_workflow_participants_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof ContentEntityInterface) {
        return $entity;
      }
    }
    throw new AccessException(sprintf('%s is not a valid entity route. The WorkflowParticipantsAccessChecker access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
