<?php

namespace Drupal\drd;

/**
 * Implements ActionPermissions class.
 */
class ActionPermissions {

  /**
   * Get a list of all DRD entity related list of permissions.
   */
  public function permissions() {
    $actionStorage = \Drupal::entityTypeManager()->getStorage('action');
    $actions = array_filter($actionStorage->loadMultiple(),
      function ($action) {
        /** @var \Drupal\system\ActionConfigEntityInterface $action */
        return in_array($action->getType(), [
          'drd',
          'drd_host',
          'drd_core',
          'drd_domain',
        ]);
      });

    $permissions = [];
    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    foreach ($actions as $action) {
      /** @var \Drupal\drd\Plugin\Action\BaseInterface $drdAction */
      $drdAction = $action->getPlugin();
      $permissions[$drdAction->getPluginId()] = [
        'title' => t('Execute action @name', ['@name' => $action->getPlugin()->getPluginDefinition()['label']]),
        'restrict access' => $drdAction->restrictAccess(),
        'description' => '',
      ];
    }
    return $permissions;
  }

}
