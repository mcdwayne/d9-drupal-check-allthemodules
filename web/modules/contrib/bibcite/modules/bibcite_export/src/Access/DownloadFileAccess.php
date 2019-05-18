<?php

namespace Drupal\bibcite_export\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;

/**
 * Access check for file, generate by "Export all" form.
 */
class DownloadFileAccess implements AccessInterface {

  /**
   * Check if user has a permission and own the file.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check access for.
   * @param \Drupal\file\FileInterface $file
   *   File to grant access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access checking result.
   */
  public function access(AccountInterface $account, FileInterface $file) {
    return AccessResult::allowedIf($account->hasPermission('administer bibcite')
      && ($file->getOwnerId() == $account->id()));
  }

}
