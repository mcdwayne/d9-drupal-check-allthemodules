<?php

namespace Drupal\node_accessibility\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\HttpFoundation\ParameterBag;
use Drupal\node_accessibility\TypeSettingsStorage;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_accessibility
 */
class ViewAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param int $nid
   *   The node id to perform the access check against.
   * #param int|null $vid
   *   (optional) The node revision id to check against.
   *   Set to NULL to use the latest node.
   *
   * @return bool
   *   TRUE for access granted, FALSE otherwise.
   */
  public static function check_node_access(AccountInterface $account, $nid, $vid = NULL) {
    if (!$account->hasPermission('perform node accessibility validation')) {
      return FALSE;
    }

    if ($nid == 0) {
      return FALSE;
    }

    $settings = TypeSettingsStorage::loadByNodeAsArray($nid);

    if (is_numeric($vid)) {
      if (!static::nodeRevisionIsValid($nid, (int) $vid)) {
        return FALSE;
      }
    }

    if (empty($settings['node_type'])) {
      return FALSE;
    }

    if (empty($settings['enabled']) || $settings['enabled'] == 'disabled') {
      return FALSE;
    }

    return TRUE;
  }


  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match) {
    if (!$account->hasPermission('perform node accessibility validation')) {
      return AccessResult::forbidden();
    }

    $bag = $route_match->getParameters();
    $nid = (int) $bag->get('node');
    $vid = $bag->get('node_revision');
    unset($bag);

    if ($nid == 0) {
      return AccessResult::forbidden();
    }

    $settings = TypeSettingsStorage::loadByNodeAsArray($nid);

    if (is_numeric($vid)) {
      if (!$this->nodeRevisionIsValid($nid, (int) $vid)) {
        return AccessResult::forbidden();
      }
    }

    if (empty($settings['node_type'])) {
      return AccessResult::forbidden();
    }

    if (empty($settings['enabled']) || $settings['enabled'] == 'disabled') {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Checks to see if the node revision is a valid revision for the node.
   *
   * @param int $nid
   *    The numeric node id.
   * @param int $vid
   *    The numeric node revision id.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  private static function nodeRevisionIsValid($nid, $vid) {
    $node_type = NULL;
    try {
      $query = \Drupal::database()->select('node', 'n');
      $query->innerJoin('node_revision', 'nr', 'n.nid = nr.nid');
      $query->addField('n', 'nid', 'nid');
      $query->addField('nr', 'vid', 'vid');
      $query->condition('n.nid', $nid);
      $query->condition('nr.vid', $vid);

      $existing = $query->execute()->fetchObject();
      if ($existing && $existing->nid == $nid) {
        return TRUE;
      }
    }
    catch (Exception $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table or {node_revision} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }
    catch (Error $e) {
      \Drupal::logger('node_accessibility')->error("Failed to select from {node} table or {node_revision} table, exception: @exception.", ['@exception' => $e->getMessage()]);
    }

    return FALSE;
  }
}
