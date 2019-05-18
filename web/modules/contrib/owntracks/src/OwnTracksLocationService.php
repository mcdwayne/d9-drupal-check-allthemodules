<?php

namespace Drupal\owntracks;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the owntracks location service.
 */
class OwnTracksLocationService {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * OwnTracksLocationService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a user's location records.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to get the track for.
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   The date of the track.
   * @param string $tracker_id
   *   A tracker ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @return array
   *   The user's track.
   */
  public function getUserTrack(AccountInterface $account, DrupalDateTime $date, $tracker_id = NULL) {
    $track = [];

    $from = DrupalDateTime::createFromArray([
      'day'    => $date->format('j'),
      'month'  => $date->format('n'),
      'year'   => $date->format('Y'),
      'hour'   => 0,
      'minute' => 0,
      'second' => 0,
    ])->format('U');

    $till = DrupalDateTime::createFromArray([
      'day'    => $date->format('j'),
      'month'  => $date->format('n'),
      'year'   => $date->format('Y'),
      'hour'   => 23,
      'minute' => 59,
      'second' => 59,
    ])->format('U');

    $storage = $this->entityTypeManager
      ->getStorage('owntracks_location');
    $query = $storage->getQuery()
      ->condition('uid', $account->id())
      ->condition('tst', [$from, $till], 'BETWEEN')
      ->sort('tst');

    if (!empty($tracker_id)) {
      $query->condition('tid', $tracker_id);
    }

    $result = $query->execute();

    if (!empty($result)) {
      $entities = $storage->loadMultiple($result);

      foreach ($entities as $owntracks_location) {
        $track[] = $owntracks_location->getLocation();
      }
    }

    return empty($track) ? NULL : $track;
  }

}
