<?php

namespace Drupal\akismet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Default controller for the Akismet module.
 */
class DefaultController extends ControllerBase {

  /**
   * Menu access callback; Determine access to report to Akismet.
   *
   * @param $entity
   *   The entity type of the data to report.
   * @param $id
   *   The entity id of the data to report.
   *
   * @return bool
   *   TRUE if the current user has access, FALSE if not.
   */
  function reportAccess($entity, $id) {
    // Retrieve information about all protectable forms. We use the first valid
    // definition, because we assume that multiple form definitions just denote
    // variations of the same entity (e.g. node content types).
    foreach (FormController::getProtectableForms() as $form_id => $info) {
      if (!isset($info['entity']) || $info['entity'] != $entity) {
        continue;
      }
      // If there is a 'report access callback', invoke it.
      if (isset($info['report access callback']) && function_exists($info['report access callback'])) {
        $function = $info['report access callback'];
        return $function($entity, $id);
      }
      // Otherwise, if there is a 'report access' list of permissions, iterate
      // over them.
      if (isset($info['report access'])) {
        foreach ($info['report access'] as $permission) {
          if (\Drupal::currentUser()->hasPermission($permission)) {
            return TRUE;
          }
        }
      }
    }
    // If we end up here, then the current user is not permitted to report this
    // content.
    return FALSE;
  }

  /**
   * Access callback; check if the module is configured.
   *
   * This function does not actually check whether the Akismet key is valid for
   * the site, but just if the key has been entered.
   *
   * @param \Drupal\Core\Session\AccountInterface $permission
   *   An optional permission string to check with \Drupal::currentUser()->hasPermission().
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  function access($permission = FALSE) {
    $configured = \Drupal::config('akismet.settings')->get('api_key');
    if ($configured && $permission) {
      return AccessResult::allowedIfHasPermission($permission, \Drupal::currentUser());
    }
    else {
      return AccessResult::allowedIf($configured);
    }
  }
}
