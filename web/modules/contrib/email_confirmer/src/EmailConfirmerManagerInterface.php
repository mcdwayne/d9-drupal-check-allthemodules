<?php

namespace Drupal\email_confirmer;

/**
 * Email confirmation service interface.
 */
interface EmailConfirmerManagerInterface {

  /**
   * Starts an email confirmation process.
   *
   * Existing confirmation for the given email address will be used if it is in
   * pending status, otherwise a new one will be created. Confirmation request
   * email is sent by this method.
   *
   * @param string $email
   *   Email address to be confirmed.
   * @param array $data
   *   An optional property map to store with the confirmation.
   * @param string $realm
   *   A realm to which this confirmation belongs. Tipically, a module name.
   *
   * @return \Drupal\email_confirmer\EmailConfirmationInterface
   *   The email confirmation entity.
   */
  public function confirm($email, array $data = [], $realm = '');

  /**
   * Search for existent confirmation entities for a given email address.
   *
   * @param string $email
   *   The email address.
   * @param string $status
   *   Filter by confirmation status.
   * @param int $limit
   *   Max results count. Defaults to no limit.
   * @param string $realm
   *   A realm to filter by.
   *
   * @return \Drupal\email_confirmer\EmailConfirmationInterface[]
   *   Array of confirmations found, most recent first. Empty array if none
   *   found.
   */
  public function getConfirmations($email, $status = FALSE, $limit = 0, $realm = '');

  /**
   * Search for the recent confirmation for a given email address.
   *
   * @param string $email
   *   The email address.
   * @param string $status
   *   Filter by confirmation status.
   * @param string $realm
   *   A realm to filter by.
   *
   * @return \Drupal\email_confirmer\EmailConfirmationInterface
   *   The email confirmation entity. NULL if not found.
   */
  public function getConfirmation($email, $status = FALSE, $realm = '');

  /**
   * Creates a new email confirmation entity.
   *
   * @param string $email
   *   The email address to confirm.
   *
   * @return \Drupal\email_confirmer\EmailConfirmationInterface
   *   The new email confirmation entity.
   */
  public function createConfirmation($email);

}
