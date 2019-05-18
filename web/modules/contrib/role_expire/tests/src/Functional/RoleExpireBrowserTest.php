<?php

namespace Drupal\Tests\role_expire\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Adds helper functions.
 */
class RoleExpireBrowserTest extends BrowserTestBase {

  /**
   * Writes and then gets role expiration for a given user and role ID.
   *
   * @param $account_id
   *   User ID.
   * @param string $rid
   *   Role ID.
   * @param int $expiration
   *   The expiration timestamp.
   * @return array
   *   Array with the expiration time.
   */
  protected function setAndGetExpiration($account_id, $rid, $expiration) {
    $this->apiService->writeRecord($account_id, $rid, $expiration);
    $saved_expiration = $this->apiService->getUserRoleExpiryTime($account_id, $rid);

    return $saved_expiration;
  }

  /**
   * Creates a role with optional expiration via UI.
   *
   * @param $rid
   *   Role ID.
   * @param $roleName
   *   Role name.
   * @param string $expiration
   *   The strtotime-compatible duration string.
   */
  protected function createRoleWithOptionalExpirationUI($roleName, $rid, $expiration = '') {
    $this->drupalGet('admin/people/roles/add');
    $this->getSession()->getPage()->fillField('Role name', $roleName);
    $this->getSession()->getPage()->fillField('Machine-readable name', $rid);
    if (!empty($expiration)) {
      $this->getSession()->getPage()->fillField('Default duration for the role', $expiration);
    }
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->statusCodeEquals(200);
  }
}
