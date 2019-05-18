<?php

namespace Drupal\expire_reset_pass_link;

/**
 * Class ResetPasswordHistory.
 *
 * @package Drupal\expire_reset_pass_link
 */
class ResetPasswordHistory {

  /**
   * Update reset password history.
   *
   * @params
   * $uid  user id
   * $timestamp  timestamp
   */
  public static function mergeResetPasswordTimeStampHistory($uid, $timestamp) {
    $result = \Drupal::database()
      ->merge('user__reset_password_timestamp_history')
      ->key(['uid' => $uid])
      ->insertFields([
        'uid' => $uid,
        'timestamp' => $timestamp,
      ])
      ->updateFields([
        'timestamp' => $timestamp,
      ])
      ->execute();

    return $result;
  }

  /**
   * Check the latest timestamp of reset password.
   *
   * @params
   * $uid  user id
   * $timestamp  timestamp
   */
  public static function isLatestResetPasswordTimeStamp($uid, $timestamp) {
    $is_latest = FALSE;
    $query = \Drupal::database()
      ->select('user__reset_password_timestamp_history', 'urph');
    $query->fields('urph', ['uid', 'timestamp ']);
    $query->condition('urph.uid', $uid);
    $query->condition('urph.timestamp', $timestamp);
    $result = $query->execute();
    while ($row = $result->fetchAssoc()) {
      if ($row['uid'] > 0) {
        $is_latest = TRUE;
      }
    }

    return $is_latest;
  }

}
