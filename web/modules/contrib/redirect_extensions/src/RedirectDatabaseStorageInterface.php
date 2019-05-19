<?php

namespace Drupal\redirect_extensions;

/**
 * Interface RedirectDatabaseStorageInterface.
 */
interface RedirectDatabaseStorageInterface {

  /**
   * Insert a redirect.
   *
   * @param string $redirect_id
   *   New redirect ID.
   */
  public function insertRedirect($redirect_id);

  /**
   * Check if redirect id exists.
   *
   * @param string $redirect_id
   *   Redirect ID to be checked.
   *
   * @return bool
   *   True if redirect exists.
   */
  public function redirectExists($redirect_id);

  /**
   * Update a redirect.
   *
   * @param string $redirect_id
   *   ID of redirect being updated.
   */
  public function updateRedirect($redirect_id);

  /**
   * Delete a redirect.
   *
   * @param string $redirect_id
   *   ID of redirect being deleted.
   */
  public function deleteRedirect($redirect_id);

}
