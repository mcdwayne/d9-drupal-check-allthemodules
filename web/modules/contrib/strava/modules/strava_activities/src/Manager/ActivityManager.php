<?php

namespace Drupal\strava_activities\Manager;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\strava_activities\Entity\Activity;

class ActivityManager {

  /**
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * ActivityManager constructor.
   *
   * @param \Drupal\Core\Database\Driver\mysql\Connection $connection
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(Connection $connection, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->connection = $connection;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param array $activity
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  public function createActivity($activity) {

    $new_activity = Activity::create(
      [
        'id' => $activity['id'],
        'athlete' => $activity['athlete']['id'],
        'name' => $activity['name'],
        'external_id' => $activity['external_id'],
        'upload_id' => $activity['upload_id'],
        'gear_id' => $activity['gear_id'],
        'map_id' => $activity['map']['id'],
        'map_summary_polyline' => $activity['map']['summary_polyline'],
        'distance' => $activity['distance'],
        'moving_time' => $activity['moving_time'],
        'elapsed_time' => $activity['elapsed_time'],
        'total_elevation_gain' => $activity['total_elevation_gain'],
        'elev_high' => $activity['elev_high'],
        'elev_low' => $activity['elev_low'],
        'average_speed' => $activity['average_speed'],
        'max_speed' => $activity['max_speed'],
        'average_watts' => $activity['average_watts'],
        'max_watts' => $activity['max_watts'],
        'device_watts' => $activity['device_watts'],
        'weighted_average_watts' => $activity['weighted_average_watts'],
        'kilojoules' => $activity['kilojoules'],
        'type' => $activity['type'],
        'kudos_count' => $activity['kudos_count'],
        'achievement_count' => $activity['achievement_count'],
        'start_lat' => $activity['start_latlng'][0],
        'start_long' => $activity['start_latlng'][1],
        'end_lat' => $activity['end_latlng'][0],
        'end_long' => $activity['end_latlng'][1],
        'comment_count' => $activity['comment_count'],
        'athlete_count' => $activity['athlete_count'],
        'photo_count' => $activity['photo_count'],
        'total_photo_count' => $activity['total_photo_count'],
        'flagged' => $activity['flagged'],
        'trainer' => $activity['trainer'],
        'commute' => $activity['commute'],
        'manual' => $activity['manual'],
        'private' => $activity['private'],
        'status' => (int) !$activity['private'],
        'created' => strtotime($activity['start_date']),
        'changed' => \Drupal::time()->getRequestTime(),
        'description__value' => '', // @TODO: finish this.
      ]
    );

    $new_activity->save();

    return $new_activity;
  }

  /**
   * @param $activity
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  public function updateActivity($activity) {

    $updated_activity = Activity::load($activity['id']);

    $updated_activity->setName($activity['name']);
    $updated_activity->setAthlete($activity['athlete']['id']);
    $updated_activity->setPublished((int) !$activity['private']);
    $updated_activity->setPrivate($activity['private']);
    $updated_activity->setFlagged($activity['flagged']);
    $updated_activity->setCommute($activity['commute']);
    $updated_activity->setTrainer($activity['trainer']);
    $updated_activity->setManual($activity['manual']);
    $updated_activity->setCreatedTime(strtotime($activity['start_date']));
    $updated_activity->setMapId($activity['map']['id']);
    $updated_activity->setMapSummaryPolyline($activity['map']['summary_polyline']);
    $updated_activity->setCommentCount($activity['comment_count']);
    $updated_activity->setAchievementCount($activity['achievement_count']);
    $updated_activity->setKudosCount($activity['kudos_count']);
    $updated_activity->setAthleteCount($activity['athlete_count']);
    $updated_activity->setPhotoCount($activity['photo_count']);
    $updated_activity->setTotalPhotoCount($activity['total_photo_count']);
    $updated_activity->setGearId($activity['gear_id']);
    $updated_activity->setKiloJoules($activity['kilojoules']);
    $updated_activity->setAverageSpeed($activity['average_speed']);
    $updated_activity->setMaxSpeed($activity['max_speed']);
    $updated_activity->setDeviceWatts($activity['device_watts']);
    $updated_activity->setStartLatLong($activity['start_latlng'][0], $activity['start_latlng'][1]);
    $updated_activity->setEndLatLong($activity['end_latlng'][0], $activity['end_latlng'][1]);
    if (isset($activity['average_watts'])) {
      $updated_activity->setAverageWatts($activity['average_watts']);
    }
    if (isset($activity['max_watts'])) {
      $updated_activity->setMaxWatts($activity['max_watts']);
    }
    if (isset($activity['weighted_average_watts'])) {
      $updated_activity->setWeightedAverageWatts($activity['weighted_average_watts']);
    }

    // Get an activity photo.
    if ($activity['photos']['count']) {
      $updated_activity->setPhoto($activity['photos']['primary']['urls']['600']);
      $updated_activity->setSmallPhoto($activity['photos']['primary']['urls']['100']);
    }

    // Process extra activity properties if return object is a DetailedActivity.
    if ($activity['resource_state'] > 2) {
      $updated_activity->setDescription($activity['description']);
      $updated_activity->setMapPolyline($activity['map']['polyline']);
      $updated_activity->setGearName($activity['gear']['name']);
      $updated_activity->setCalories($activity['calories']);
    }

    $updated_activity->setChangedTime(\Drupal::time()->getRequestTime());
    $updated_activity->save();

    return $updated_activity;
  }

  /**
   * @param $id
   *
   * @return mixed
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteActivity($id) {
    $activity = Activity::load($id);
    return $activity->delete();
  }

  /**
   * Loads existing activity object by given property and value.
   *
   * Note that first matching activity is returned.
   *
   * @param string $field
   *   Activity entity field to search from.
   * @param string $value
   *   Value to search for.
   *
   * @return \Drupal\strava_activities\Entity\Activity|false
   *   Stored Strava activity object if found
   *   False otherwise
   */
  public function loadActivityByProperty($field, $value) {
    $activities = $this->entityTypeManager
      ->getStorage('activity')
      ->loadByProperties([$field => $value]);

    if (!empty($activities)) {
      return current($activities);
    }

    // If activity was not found, return FALSE.
    return FALSE;
  }

}
