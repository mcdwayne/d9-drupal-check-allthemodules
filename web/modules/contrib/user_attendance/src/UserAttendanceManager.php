<?php

namespace Drupal\user_attendance;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user_attendance\Entity\UserAttendanceTypeInterface;
use Drupal\Core\Datetime\DrupalDateTime;

class UserAttendanceManager implements UserAttendanceManagerInterface {
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userAttendanceStorage;

  /**
   * Constructs a new UserAttendanceManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface entityTypeManager */
    $this->entityTypeManager = $entity_type_manager;

    /** @var \Drupal\Core\Entity\EntityStorageInterface channelStorage */
    $this->userAttendanceStorage = $this->entityTypeManager
      ->getStorage('user_attendance');

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface channelMessageStorage */
    $this->userStorage = $this->entityTypeManager
      ->getStorage('user');
  }

  /**
   * @inheritDoc
   */
  public function getCurrentActiveUserAttendance(AccountInterface $user, UserAttendanceTypeInterface $bundle) {
    $query = \Drupal::entityQuery('user_attendance');
    $query->condition('user_id', $user->id());
    $query->condition('type', $bundle->id());
    $query->exists('start');
    $query->notExists('end');
    $query->sort('start', 'DESC');
    $query->range(0, 1);

    // If attendance period is by day, we want to only get the user
    // attendance of this day.
    $attendance_period_type = $bundle->get('attendance_period_type');
    if ($attendance_period_type == 'by_day') {
      $date_string = date('Y-m-d', REQUEST_TIME);
      $dateTime = DrupalDateTime::createFromFormat('Y-m-d', $date_string, drupal_get_user_timezone());
      $query->condition('start', $dateTime->format('U'), '>=');
    }

    $ids = $query->execute();

    // Load the entity.
    if (!empty($ids)) {
      $id = reset($ids);
      $userAttendance = $this->userAttendanceStorage->load($id);
      return $userAttendance;
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getLastActiveUserAttendance(AccountInterface $user, UserAttendanceTypeInterface $bundle) {
    $query = \Drupal::entityQuery('user_attendance');
    $query->condition('user_id', $user->id());
    $query->condition('type', $bundle->id());
    $query->exists('end');
    $query->sort('end', 'DESC');
    $query->sort('id', 'DESC');

    // If attendance period is by day, we want to only get the user
    // attendance of this day.
    $attendance_period_type = $bundle->get('attendance_period_type');
    if ($attendance_period_type == 'by_day') {
      $date_string = date('Y-m-d', REQUEST_TIME);
      $dateTime = DrupalDateTime::createFromFormat('Y-m-d', $date_string, drupal_get_user_timezone());
      $query->condition('start', $dateTime->format('U'), '>=');
    }

    $query->range(0, 1);
    $ids = $query->execute();

    // Load the entity.
    if (!empty($ids)) {
      $id = reset($ids);
      $userAttendance = $this->userAttendanceStorage->load($id);
      return $userAttendance;
    }

    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function userIsAttending(AccountInterface $user, UserAttendanceTypeInterface $bundle) {
    $userAttendance = $this->getCurrentActiveUserAttendance($user, $bundle);

    if ($userAttendance) {
      return FALSE;
    }

    // Only create a new record if the previous one is out of range by the
    // number of seconds from the duplicate protection setting.
    $lastUserAttendance = $this->getLastActiveUserAttendance($user, $bundle);
    if (!$lastUserAttendance || (REQUEST_TIME - $lastUserAttendance->getEndTime()) > $bundle->get('duplicate_protection')) {
      // Create user attendance with current timestamp.
      $entity_values = [
        'user_id' => $user->id(),
        'type' => $bundle->id(),
        'start' => REQUEST_TIME,
      ];

      /** @var \Drupal\user_attendance\Entity\UserAttendanceInterface $userAttendance */
      $userAttendance = $this->userAttendanceStorage->create($entity_values);
    }
    // If in range of the duplicate protection setting. Unset the end time to
    // make sure the previous user attendance is the active one now.
    else {
      $userAttendance = $lastUserAttendance;
      $userAttendance->setEndTime(NULL);
    }

    $userAttendance->save();
  }

  /**
   * @inheritDoc
   */
  public function userIsNotAttending(AccountInterface $user, UserAttendanceTypeInterface $bundle) {
    // Get current active user attendance.
    $userAttendance = $this->getCurrentActiveUserAttendance($user, $bundle);

    // Continue if we have found one.
    if ($userAttendance) {
      // Validate if we have duplicate protection. If so, can we continue?
      $duplicate_protection = $bundle->get('duplicate_protection');
      if (empty($duplicate_protection) || (REQUEST_TIME - $userAttendance->getStartTime()) > $duplicate_protection) {
        $userAttendance->setEndTime(REQUEST_TIME);
        $userAttendance->save();
      }
      else {
        $userAttendance->delete();
      }
    }

    return $userAttendance;
  }
}