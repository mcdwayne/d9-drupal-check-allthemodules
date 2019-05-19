<?php

namespace Drupal\strava_athletes\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Athlete entities.
 *
 * @ingroup strava
 */
interface AthleteInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Athlete uid.
   *
   * @return int
   */
  public function getUid();

  /**
   * Set the Athlete uid.
   *
   * @param int $uid
   */
  public function setUid($uid);


  /**
   * Gets the Athlete id.
   *
   * @return int
   */
  public function getId();

  /**
   * Set the Athlete id.
   *
   * @param int $id
   */
  public function setId($id);

  /**
   * Gets the first name.
   *
   * @return string
   */
  public function getFirstName();

  /**
   * Sets the first name.
   *
   * @param string $firstname
   */
  public function setFirstName($firstname);

  /**
   * Gets the last name.
   *
   * @return string
   */
  public function getLastName();

  /**
   * Sets the last name.
   *
   * @param string $lastname
   */
  public function setLastName($lastname);

  /**
   * Gets the large profile image.
   *
   * @return string
   */
  public function getProfile();

  /**
   * Sets the large profile image.
   *
   * @param string $profile
   */
  public function setProfile($profile);
  /**
   * Gets the medium profile image.
   *
   * @return string
   */
  public function getMediumProfile();

  /**
   * Sets the medium profile image.
   *
   * @param string $profile
   */
  public function setMediumProfile($profile);

  /**
   * Gets the city.
   *
   * @return string
   */
  public function getCity();

  /**
   * Sets the city.
   *
   * @param string $city
   */
  public function setCity($city);

  /**
   * Gets the state.
   *
   * @return string
   */
  public function getState();

  /**
   * Sets the state.
   *
   * @param string $state
   */
  public function setState($state);

  /**
   * Gets the country.
   *
   * @return string
   */
  public function getCountry();

  /**
   * Sets the country.
   *
   * @param string $country
   */
  public function setCountry($country);

  /**
   * Gets the sex.
   *
   * @return string
   */
  public function getSex();

  /**
   * Sets the sex.
   *
   * @param string $sex
   */
  public function setSex($sex);

  /**
   * Gets the premium.
   *
   * @return boolean
   */
  public function getPremium();

  /**
   * Sets the premium.
   *
   * @param boolean $premium
   */
  public function setPremium($premium);

  /**
   * Gets the follower count.
   *
   * @return int
   */
  public function getFollowerCount();

  /**
   * Sets the follower count.
   *
   * @param int $count
   */
  public function setFollowerCount($count);

  /**
   * Gets the friend count.
   *
   * @return int
   */
  public function getFriendCount();

  /**
   * Sets the friend count.
   *
   * @param int $count
   */
  public function setFriendCount($count);

  /**
   * Gets the FTP.
   *
   * @return int
   */
  public function getFtp();

  /**
   * Sets the FTP.
   *
   * @param int $ftp
   */
  public function setFtp($ftp);

  /**
   * Gets the measurement preference.
   *
   * @return string
   */
  public function getMeasurementPreference();

  /**
   * Sets the measurement preference.
   *
   * @param string $measurement_preference
   */
  public function setMeasurementPreference($measurement_preference);

  /**
   * Gets the weight.
   *
   * @return float
   */
  public function getWeight();

  /**
   * Sets the weight.
   *
   * @param float $weight
   */
  public function setWeight($weight);

  /**
   * Gets the clubs.
   *
   * @return array
   */
  public function getClubs();

  /**
   * Sets the clubs.
   *
   * @param array $clubs
   */
  public function setClubs($clubs);

  /**
   * Gets the Athlete creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Athlete.
   */
  public function getCreatedTime();

  /**
   * Sets the Athlete creation timestamp.
   *
   * @param int $timestamp
   *   The Athlete creation timestamp.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Athlete published status indicator.
   *
   * Unpublished Athlete are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Athlete is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Athlete.
   *
   * @param bool $published
   *   TRUE to set this Athlete to published, FALSE to set it to unpublished.
   */
  public function setPublished($published);

}
