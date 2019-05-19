<?php

namespace Drupal\simplenews_stats;

use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityInterface;
use Drupal\simplenews\SubscriberInterface;

/**
 * The simplenews stats engine.
 */
class SimplenewsStatsEngine {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * SimplenewsStatsEngine constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request statck.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The Entity type manager.
   */
  public function __construct(RequestStack $request, CurrentRouteMatch $current_route_match, EntityTypeManager $entity_type_manager) {
    $this->request           = $request;
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Add stat by tag.
   *
   * @param string $tag
   *   The tag to log.
   * @param type $path
   *   The reference path.
   */
  public function addStatTags($tag, $path = NULL) {

    $entities = $this->getTagEntities($tag);
    // Escape if the entity doesn't exist.
    if ($entities === FALSE) {
      return;
    }

    // Use current path if path is empty.
    if (empty($path)) {
      $path = $this->request->getCurrentRequest()->getPathInfo();
    }

    $this->logHit($entities['subscriber'], $entities['entity'], $this->currentRouteMatch->getRouteName(), $path);
  }

  /**
   * Return entities associated to the tag.
   *
   * @param string $tag 
   *   The tag.
   *
   * @return bool|array 
   *   Array of entities or false.
   */
  public function getTagEntities($tag) {

    if (!preg_match('/^u[0-9]*nl[0-9]*/', $tag)) {
      return FALSE;
    }

    $args = preg_split("/(u)|(nl)/", $tag);

    if (count($args) != 3) {
      return FALSE;
    }

    $subscriber = $this->entityTypeManager->getStorage('simplenews_subscriber')->load($args[1]);
    $entity     = $this->entityTypeManager->getStorage('node')->load($args[2]);

    if ($subscriber == FALSE || $entity == FALSE) {
      return FALSE;
    }

    return [
      'subscriber' => $subscriber,
      'entity'     => $entity,
    ];
  }

  /**
   * Store in data base the newsletter hit (click or view).
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber who has just done an action.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity used as simplenews.
   * @param string $route_name
   *   The route name.
   * @param string $path
   *   The path to log (link clicked).
   */
  protected function logHit(SubscriberInterface $subscriber, EntityInterface $entity, $route_name, $path) {
    // If the road is that of the pixel image it's a view else otherwise it's a click.
    $action= ($route_name === 'simplenews_stats.hit_view')?  'view': 'click';

    $data = [
      'uid'         => $subscriber->getUserId(),
      'snid'        => $subscriber->id(),
      'email'       => $subscriber->getMail(),
      'title'       => $action,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id'   => $entity->id(),
      'route_path'  => substr($path, 0, 255),
      'created'     => \Drupal::time()->getRequestTime(),
    ];

    $this->globalStatUpdate($subscriber, $entity, $action);

    $storage         = $this->entityTypeManager->getStorage('simplenews_stats_item');
    $simplenews_stat = $storage->create($data);
    $simplenews_stat->save();
  }

  /**
   * Increase the sent counter of the given Entity.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity used as simplenews.
   */
  public function logHitSent(SubscriberInterface $subscriber, EntityInterface $entity) {
    $simplenews_stats = $this->getSimplenewsStats($subscriber, $entity);
    $simplenews_stats->increaseTotalMail()
      ->save();
  }

  /**
   * Update the global stat entry.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity used as simplenews.
   * @param string $action
   *   The action (click or view).
   */
  protected function globalStatUpdate(SubscriberInterface $subscriber, EntityInterface $entity, $action) {
    $storage   = $this->entityTypeManager->getStorage('simplenews_stats');
    $entity_gs = $storage->getFromRelatedEntity($entity);

    if (!$entity_gs) {
      $entity_gs = $storage->createFromSubscriberAndEntity($subscriber, $entity);
    }

    $entity_gs->{'increase' . ucfirst($action)}()
      ->save();
  }

  /**
   * Return the simplenews stats entities.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   The simplenews subscriber.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The Entity used as simplenews.
   * 
   * @return \Drupal\simplenews_stats\Entity\SimplenewsStats
   *   The simplenews stats entity.
   */
  protected function getSimplenewsStats(SubscriberInterface $subscriber, EntityInterface $entity) {
    $storage   = $this->entityTypeManager->getStorage('simplenews_stats');
    $entity_gs = $storage->getFromRelatedEntity($entity);

    // return the entity if exist. 
    if ($entity_gs){
      return $entity_gs;
    }

    // Return a new one.
    return $storage->createFromSubscriberAndEntity($subscriber, $entity);
  }

}
