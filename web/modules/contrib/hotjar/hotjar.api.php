<?php
/**
 * @file
 * Hooks provided by the Hotjar module.
 */

use Drupal\Core\Access\AccessResult;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control access to a Hotjar tracking code.
 *
 * Modules may implement this hook if they want to disable tracking for some
 * reasons.
 *
 * @return \Drupal\Core\Access\AccessResultInterface|bool|null
 *   - HOTJAR_ACCESS_ALLOW: If tracking is allowed.
 *   - HOTJAR_ACCESS_DENY: If tracking is disabled.
 *   - HOTJAR_ACCESS_IGNORE: If tracking check is
 *
 * @ingroup node_access
 */
function hook_hotjar_access() {
  // Disable for frontpage.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    return AccessResult::forbidden();
  }
  return AccessResult::neutral();
}

/**
 * Alter results of Hotjar access check results.
 */
function hook_hotjar_access_alter(&$results) {
  // Force disable for frontpage.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $result = AccessResult::forbidden();
  }
  else {
    $result = AccessResult::neutral();
  }
  $results['my_module_check'] = $result;
}

/**
 * @} End of "addtogroup hooks".
 */
