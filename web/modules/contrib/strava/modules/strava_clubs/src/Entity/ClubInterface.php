<?php

namespace Drupal\strava_clubs\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Club entities.
 *
 * @ingroup strava
 */
interface ClubInterface extends ContentEntityInterface {

  /**
   * Gets the club id.
   *
   * @return int
   */
  public function getId();

  /**
   * Sets the club id.
   *
   * @param int $id
   */
  public function setId($id);

  /**
   * Gets the resource state.
   *
   * @return int
   */
  public function getResourceState();

  /**
   * Sets the resource state
   *
   * @param int $resource_state
   */
  public function setResourceState($resource_state);

    /**
   * Gets the club name.
   *
   * @return string
   *   Name of the Club.
   */
  public function getName();

  /**
   * Sets the Club name.
   *
   * @param string $name
   */
  public function setName($name);

  /**
   * Gets the profile.
   *
   * @return int
   */
  public function getProfile();

  /**
   * Sets the profile
   *
   * @param string $profile
   */
  public function setProfile($profile);

  /**
   * Gets the cover photo.
   *
   * @return string
   */
  public function getCoverPhoto();

  /**
   * Sets the cover photo
   *
   * @param string $cover_photo
   */
  public function setCoverPhoto($cover_photo);

  /**
   * Gets the description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Sets the description
   *
   * @param string $description
   */
  public function setDescription($description);

  /**
   * Gets the sport type
   *
   * @return string
   */
  public function getSportType();

  /**
   * Sets the sport type
   *
   * @param string $sport_type
   */
  public function setSportType($sport_type);

  /**
   * Gets the city.
   *
   * @return string
   */
  public function getCity();

  /**
   * Sets the city
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
   * Sets the state
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
   * Sets the cover country
   *
   * @param string $country
   */
  public function setCountry($country);

  /**
   * Gets the member count.
   *
   * @return integer
   */
  public function getMemberCount();

  /**
   * Sets the member count
   *
   * @param int $member_count
   */
  public function setMemberCount($member_count);

  /**
   * Gets the url
   *
   * @return string
   */
  public function getUrl();

  /**
   * Sets the  url
   *
   * @param string $url
   */
  public function setUrl($url);

  /**
   * Returns the Club published status indicator.
   *
   * Unpublished Club are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Club is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Club.
   *
   * @param bool $published
   *   TRUE to set this Club to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\strava_clubs\Entity\ClubInterface
   *   The called Club entity.
   */
  public function setPublished($published);

}
