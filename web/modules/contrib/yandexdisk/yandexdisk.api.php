<?php

/**
 * @file
 * Hooks provided by Yandex.Disk module.
 */

use Drupal\Core\Session\AccountInterface;

/**
 * Controls access to a Yandex.Disk file/directory.
 *
 * Modules may implement this hook if they want to have a say in whether or not
 * a given user has access to perform a given operation on a uri.
 *
 * If your module does not want to actively grant or block access, simply return
 * nothing (or NULL). Blindly returning FALSE will break other yandexdisk access
 * modules.
 *
 * @param string $op
 *   The operation to be performed on the uri. Possible values are:
 *   - 'get'.
 *   - 'put'.
 *   - 'mkcol'.
 *   - 'copy'.
 *   - 'move'.
 *   - 'delete'.
 *   - 'propfind'.
 *   - 'proppatch'.
 * @param string $uri
 *   The Yandex.Disk uri (yandexdisk://yandex_username/path) on which to perform
 *   the access check.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user object to perform the access check operation on.
 *
 * @return bool|null
 *   - TRUE: If the operation is to be allowed.
 *   - FALSE: If the operation is to be denied.
 *   - NULL: To not affect this operation at all.
 */
function hook_yandexdisk_access($op, $uri, AccountInterface $account) {
  $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
  if (isset($wrapper->user)) {
    $token = \Drupal::service('yandex_oauth')->get($wrapper->user, FALSE);
  }

  // Not all Yandex accounts belong to authenticated users. For this case we
  // don't give anonymous users access to 'own' Yandex.Disks (accounts
  // associated with uid 0).
  $own_account = !empty($token) && $account->id() && $account->id() == $token->uid;

  switch ($op) {
    case 'get':
    case 'propfind':
      if ($account->hasPermission('view any yandexdisk resources') || $own_account && $account->hasPermission('view own yandexdisk resources')) {
        return TRUE;
      }
      break;

    case 'put':
    case 'mkcol':
    case 'copy':
    case 'move':
    case 'delete':
    case 'proppatch':
      if ($account->hasPermission('edit any yandexdisk resources') || $own_account && $account->hasPermission('edit own yandexdisk resources')) {
        return TRUE;
      }
      break;
  }
}
