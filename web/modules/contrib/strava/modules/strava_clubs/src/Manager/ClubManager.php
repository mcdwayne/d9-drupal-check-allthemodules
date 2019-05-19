<?php

namespace Drupal\strava_clubs\Manager;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\strava_clubs\Entity\Club;

class ClubManager {

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
   * AthleteManager constructor.
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

  public function processClubs($clubs) {

    $club_list = [];

    if (empty($clubs)) {
      return $club_list;
    }

    foreach ($clubs as $club) {

      $club_found = $this->loadClubByProperty('id', $club['id']);

      if (!$club_found) {
        // If the club wasn't found create it and return it.
        //$club_list += $this->createClub($club);
        array_push($club_list, $this->createClub($club));
      }
      else {
        // If the club was found update it and return it.
        //$club_list += $this->updateClub($club);
        array_push($club_list, $this->updateClub($club));
      }
    }

    return $club_list;
  }

  public function createClub($club) {

    $club = Club::create(
      [
        'id' => $club['id'],
        'name' => $club['name'],
        'profile' => $club['profile'],
        'cover_photo' => $club['cover_photo'],
        'sport_type' => $club['sport_type'],
        'city' => $club['city'],
        'state' => $club['state'],
        'country' => $club['country'],
        'member_count' => $club['member_count'],
        'url' => $club['url'],
      ]
    );

    $club->save();

    return $club;
  }

  public function updateClub($club) {

    $updated_club = Club::load($club['id']);

    $updated_club->setName($club['name']);
    $updated_club->setProfile($club['profile']);
    $updated_club->setCoverPhoto($club['cover_photo']);
    $updated_club->setSportType($club['sport_type']);
    $updated_club->setCity($club['city']);
    $updated_club->setCountry($club['country']);
    $updated_club->setMemberCount($club['member_count']);
    $updated_club->setUrl($club['url']);

    $updated_club->save();

    return $updated_club;
  }

  /**
   * Loads existing Club entity by given property and value.
   *
   * Note that first matching club is returned.
   *
   * @param string $field
   *   Club entity field to search from.
   * @param string $value
   *   Value to search for.
   *
   * @return \Drupal\user\Entity\User|false
   *   Drupal user account if found
   *   False otherwise
   */
  public function loadClubByProperty($field, $value) {
    $clubs = $this->entityTypeManager
      ->getStorage('club')
      ->loadByProperties([$field => $value]);

    if (!empty($clubs)) {
      return current($clubs);
    }

    // If club was not found, return FALSE.
    return FALSE;
  }
}