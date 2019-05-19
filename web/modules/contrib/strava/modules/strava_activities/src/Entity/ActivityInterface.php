<?php

namespace Drupal\strava_activities\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Activity entities.
 *
 * @ingroup strava
 */
interface ActivityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Activity name.
   *
   * @return string
   *   Name of the Activity.
   */
  public function getName();

  /**
   * Sets the Activity name.
   *
   * @param string $name
   *   The Activity name.
   *
   * @return \Drupal\strava_activities\Entity\ActivityInterface
   *   The called Activity entity.
   */
  public function setName($name);

  /**
   * Gets the Activity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Activity.
   */
  public function getCreatedTime();

  /**
   * Sets the Activity creation timestamp.
   *
   * @param int $timestamp
   *   The Activity creation timestamp.
   *
   * @return \Drupal\strava_activities\Entity\ActivityInterface
   *   The called Activity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * @return mixed
   */
  public function getDescription();

  /**
   * @param $description
   *
   * @return mixed
   */
  public function setDescription($description);

  /**
   * @return mixed
   */
  public function getDistance();

  /**
   * @param $distance
   *
   * @return mixed
   */
  public function setDistance($distance);

  /**
   * @return mixed
   */
  public function getMovingTime();

  /**
   * @param $time
   *
   * @return mixed
   */
  public function setMovingTime($time);

  /**
   * @return mixed
   */
  public function getElapsedTime();

  /**
   * @param $time
   *
   * @return mixed
   */
  public function setElapsedTime($time);

  /**
   * @return mixed
   */
  public function getElevationGain();

  /**
   * @param $elevation
   *
   * @return mixed
   */
  public function setElevationGain($elevation);

  /**
   * @return mixed
   */
  public function getElevationHigh();

  /**
   * @param $elevation
   *
   * @return mixed
   */
  public function setElevationHigh($elevation);

  /**
   * @return mixed
   */
  public function getElevationLow();

  /**
   * @param $elevation
   *
   * @return mixed
   */
  public function setElevationLow($elevation);

  /**
   * @return mixed
   */
  public function getAverageSpeed();

  /**
   * @param $speed
   *
   * @return mixed
   */
  public function setAverageSpeed($speed);

  /**
   * @return mixed
   */
  public function getMaxSpeed();

  /**
   * @param $speed
   *
   * @return mixed
   */
  public function setMaxSpeed($speed);

  /**
   * @return mixed
   */
  public function getKiloJoules();

  /**
   * @param $kilojoules
   *
   * @return mixed
   */
  public function setKiloJoules($kilojoules);

  /**
   * @return mixed
   */
  public function getCalories();

  /**
   * @param $calories
   *
   * @return mixed
   */
  public function setCalories($calories);

  /**
   * @return mixed
   */
  public function getAverageWatts();

  /**
   * @param $watts
   *
   * @return mixed
   */
  public function setAverageWatts($watts);

  /**
   * @return mixed
   */
  public function getDeviceWatts();

  /**
   * @param $device
   *
   * @return mixed
   */
  public function setDeviceWatts($device);

  /**
   * @return mixed
   */
  public function getMaxWatts();

  /**
   * @param $watts
   *
   * @return mixed
   */
  public function setMaxWatts($watts);

  /**
   * @return mixed
   */
  public function getWeightedAverageWatts();

  /**
   * @param $watts
   *
   * @return mixed
   */
  public function setWeightedAverageWatts($watts);

  /**
   * @return mixed
   */
  public function getType();

  /**
   * @param $type
   *
   * @return mixed
   */
  public function setType($type);

  /**
   * @return mixed
   */
  public function getAchievementCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setAchievementCount($count);

  /**
   * @return mixed
   */
  public function getKudosCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setKudosCount($count);

  /**
   * @return mixed
   */
  public function getAthleteCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setAthleteCount($count);

  /**
   * @return mixed
   */
  public function getCommentCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setCommentCount($count);

  /**
   * @return mixed
   */
  public function getPhotoCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setPhotoCount($count);

  /**
   * @return mixed
   */
  public function getTotalPhotoCount();

  /**
   * @param $count
   *
   * @return mixed
   */
  public function setTotalPhotoCount($count);

  /**
   * @return mixed
   */
  public function getPhoto();

  /**
   * @param $photo
   *
   * @return mixed
   */
  public function setPhoto($photo);

  /**
   * @return mixed
   */
  public function getSmallPhoto();

  /**
   * @param $photo
   *
   * @return mixed
   */
  public function setSmallPhoto($photo);

  /**
   * @return mixed
   */
  public function getMapId();

  /**
   * @param $id
   *
   * @return mixed
   */
  public function setMapId($id);

  /**
   * @return mixed
   */
  public function getMapSummaryPolyline();

  /**
   * @param $polyline
   *
   * @return mixed
   */
  public function setMapSummaryPolyline($polyline);

  /**
   * @return mixed
   */
  public function getMapPolyline();

  /**
   * @param $polyline
   *
   * @return mixed
   */
  public function setMapPolyline($polyline);

  /**
   * @return mixed
   */
  public function getPrivate();

  /**
   * @param $private
   *
   * @return mixed
   */
  public function setPrivate($private);

  /**
   * @return mixed
   */
  public function getTrainer();

  /**
   * @param $trainer
   *
   * @return mixed
   */
  public function setTrainer($trainer);

  /**
   * @return mixed
   */
  public function getCommute();

  /**
   * @param $commute
   *
   * @return mixed
   */
  public function setCommute($commute);

  /**
   * @return mixed
   */
  public function getManual();

  /**
   * @param $manual
   *
   * @return mixed
   */
  public function setManual($manual);

  /**
   * @return mixed
   */
  public function getFlagged();

  /**
   * @param $flagged
   *
   * @return mixed
   */
  public function setFlagged($flagged);

  /**
   * @return mixed
   */
  public function getGearId();

  /**
   * @param $id
   *
   * @return mixed
   */
  public function setGearId($id);

  /**
   * @return mixed
   */
  public function getGearName();

  /**
   * @param $name
   *
   * @return mixed
   */
  public function setGearName($name);

  /**
   * Get the start latitude / longitude coordinates.
   *
   * @return mixed
   */
  public function getStartLatLong();

  /**
   * Set the start latitude / longitude coordinates.
   *
   * A pair of latitude/longitude coordinates, represented as an array of 2
   * floating point numbers.
   *
   * @param $lat
   * @param $long
   *
   * @return mixed
   */
  public function setStartLatLong($lat, $long);

  /**
   * Get the end latitude / longitude coordinates.
   *
   * @return mixed
   */
  public function getEndLatLong();

  /**
   * Set the end latitude / longitude coordinates.
   *
   * A pair of latitude/longitude coordinates, represented as an array of 2
   * floating point numbers.
   *
   * @param $lat
   * @param $long
   *
   * @return mixed
   */
  public function setEndLatLong($lat, $long);

}
