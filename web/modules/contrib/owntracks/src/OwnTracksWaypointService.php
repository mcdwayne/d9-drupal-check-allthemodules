<?php

namespace Drupal\owntracks;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the owntracks waypoint service.
 */
class OwnTracksWaypointService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OwnTracksWaypointService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the waypoint id for given user id and waypoint timestamp.
   *
   * @param int $uid
   *   The user id.
   * @param int $tst
   *   The timestamp.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return mixed|null
   *   A waypoint id if found.
   */
  public function getWaypointId($uid, $tst) {
    $waypoint_id = NULL;

    $result = $this->entityTypeManager
      ->getStorage('owntracks_waypoint')
      ->getQuery()
      ->condition('uid', $uid)
      ->condition('tst', $tst)
      ->execute();

    if (!empty($result)) {
      $waypoint_id = reset($result);
    }

    return $waypoint_id;
  }

}
