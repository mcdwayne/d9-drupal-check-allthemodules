<?php
/**
 * Created by PhpStorm.
 * User: jeffreybertoen
 * Date: 26-02-17
 * Time: 21:13
 */

namespace Drupal\user_attendance;


use Drupal\Core\Session\AccountInterface;
use Drupal\user_attendance\Entity\UserAttendanceInterface;
use Drupal\user_attendance\Entity\UserAttendanceTypeInterface;

interface UserAttendanceManagerInterface {

  /**
   * Get current active attendance of the given user and attendance type.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param \Drupal\user_attendance\Entity\UserAttendanceTypeInterface $bundle
   * @return UserAttendanceInterface
   */
  public function getCurrentActiveUserAttendance(AccountInterface $user, UserAttendanceTypeInterface $bundle);

  /**
   * Get the last attendance of the given user and attendance type.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param \Drupal\user_attendance\Entity\UserAttendanceTypeInterface $bundle
   * @return UserAttendanceInterface
   */
  public function getLastActiveUserAttendance(AccountInterface $user, UserAttendanceTypeInterface $bundle);

  /**
   * Mark that the user is attending.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param \Drupal\user_attendance\Entity\UserAttendanceTypeInterface $bundle
   * @return UserAttendanceInterface
   */
  public function userIsAttending(AccountInterface $user, UserAttendanceTypeInterface $bundle);

  /**
   * Mark that the user is not attending anymore.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   * @param \Drupal\user_attendance\Entity\UserAttendanceTypeInterface $bundle
   * @return UserAttendanceInterface
   */
  public function userIsNotAttending(AccountInterface $user, UserAttendanceTypeInterface $bundle);
}