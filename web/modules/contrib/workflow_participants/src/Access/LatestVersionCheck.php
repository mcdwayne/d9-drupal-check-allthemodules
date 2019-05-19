<?php

namespace Drupal\workflow_participants\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Allow access to the latest version tab for editors and reviewers.
 */
class LatestVersionCheck implements AccessInterface {

  /**
   * The content moderation latest version access service.
   *
   * @var \Drupal\content_moderation\Access\LatestRevisionCheck
   */
  protected $inner;

  /**
   * The workflow participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * Constructs the latest version access checker.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $inner
   *   The content moderation access checker.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AccessInterface $inner, EntityTypeManagerInterface $entity_type_manager) {
    $this->inner = $inner;
    $this->participantStorage = $entity_type_manager->getStorage('workflow_participants');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Check content moderation first.
    $access = $this->inner->access($route, $route_match, $account);
    $entity = $this->loadEntity($route, $route_match);

    if ($entity) {
      $participants = $this->participantStorage->loadForModeratedEntity($entity);

      if (!$access->isAllowed()) {
        $participant_access = AccessResult::allowedIf(
          $participants->isEditor($account) || $participants->isReviewer($account)
        );
        $access = $access->orIf($participant_access);
      }

      // Add cacheable dependency regardless of access.
      $access->addCacheableDependency($participants);
    }

    return $access;
  }

  /**
   * Copy of content moderation's protected method.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   returns the Entity in question.
   *
   * @throws \Exception
   *   A generic exception is thrown if the entity couldn't be loaded. This
   *   almost always implies a developer error, so it should get turned into
   *   an HTTP 500.
   */
  protected function loadEntity(Route $route, RouteMatchInterface $route_match) {
    $entity_type = $route->getOption('_content_moderation_entity_type');

    if ($entity = $route_match->getParameter($entity_type)) {
      if ($entity instanceof EntityInterface) {
        return $entity;
      }
    }
    throw new \Exception(sprintf('%s is not a valid entity route. The LatestRevisionCheck access checker may only be used with a route that has a single entity parameter.', $route_match->getRouteName()));
  }

}
