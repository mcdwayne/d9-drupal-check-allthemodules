<?php

namespace Drupal\strava_athletes\Manager;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\strava_athletes\Entity\Athlete;
use Drupal\strava_clubs\Manager\ClubManager;
use Drupal\user\UserInterface;

class AthleteManager {

  /**
   * @var Connection
   */
  protected $connection;

  /**
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var ClubManager
   */
  protected $clubManager;

  /**
   * AthleteManager constructor.
   *
   * @param Connection $connection
   * @param LoggerChannelFactoryInterface $logger_factory
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(Connection $connection, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->connection = $connection;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set the optional service strava.club_manager.
   *
   * @param ClubManager $club_manager
   */
  public function setClubManager(ClubManager $club_manager) {
    $this->clubManager = $club_manager;
  }

  /**
   * @param \Drupal\user\UserInterface $user
   * @param array $athlete
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  public function createAthlete(UserInterface $user, $athlete) {
    $new_athlete = Athlete::create(
      [
        'uid' => $user->id(),
        'id' => $athlete['id'],
        'firstname' => $athlete['firstname'],
        'lastname' => $athlete['lastname'],
        'profile' => $athlete['profile'],
        'city' => $athlete['city'],
        'state' => $athlete['state'],
        'country' => $athlete['country'],
        'sex' => $athlete['sex'],
        'premium' => (int) $athlete['premium'],
        'changed' => $athlete['updated_at'],
        'created' => $athlete['created_at'],
      ]
    );

    $new_athlete->save();

    return $new_athlete;
  }

  /**
   * @param $athlete
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  public function updateAthlete($athlete) {
    $updated_athlete = Athlete::load($athlete['id']);

    $updated_athlete->setFirstName($athlete['firstname']);
    $updated_athlete->setLastName($athlete['lastname']);
    $updated_athlete->setProfile($athlete['profile']);
    $updated_athlete->setMediumProfile($athlete['profile_medium']);
    $updated_athlete->setCity($athlete['city']);
    $updated_athlete->setState($athlete['state']);
    $updated_athlete->setCountry($athlete['country']);
    $updated_athlete->setSex($athlete['sex']);
    $updated_athlete->setPremium((int) $athlete['premium']);
    $updated_athlete->setCreatedTime(strtotime($athlete['created_at']));

    // Process extra athlete properties if return object is a DetailedAthlete.
    if ($athlete['resource_state'] > 2) {
      $updated_athlete->setWeight($athlete['weight']);
      $updated_athlete->setFtp($athlete['ftp']);
      $updated_athlete->setFollowerCount($athlete['follower_count']);
      $updated_athlete->setFriendCount($athlete['friend_count']);
      $updated_athlete->setMeasurementPreference($athlete['measurement_preference']);

      // Also process club entities if the strava_clubs module is enabled.
      if (\Drupal::moduleHandler()->moduleExists('strava_clubs')) {
        $updated_athlete->setClubs($this->clubManager->processClubs($athlete['clubs']));
      }
    }

    $updated_athlete->setChangedTime(strtotime($athlete['updated_at']));
    $updated_athlete->save();

    return $updated_athlete;
  }

  /**
   * Loads existing athlete object by given property and value.
   *
   * Note that first matching athlete is returned.
   *
   * @param string $field
   *   Athlete entity field to search from.
   * @param string $value
   *   Value to search for.
   *
   * @return \Drupal\strava_athletes\Entity\Athlete|false
   *   Stored Strava athlete object if found
   *   False otherwise
   */
  public function loadAthleteByProperty($field, $value) {
    $athletes = $this->entityTypeManager
      ->getStorage('athlete')
      ->loadByProperties([$field => $value]);

    if (!empty($athletes)) {
      return current($athletes);
    }

    // If athlete was not found, return FALSE.
    return FALSE;
  }

}
