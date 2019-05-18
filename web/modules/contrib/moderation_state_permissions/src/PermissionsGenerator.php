<?php

namespace Drupal\moderation_state_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workflows\Entity\Workflow;

class PermissionsGenerator {

  use StringTranslationTrait;

  public static $OPERATIONS = [
    'view',
    'update',
    'delete',
  ];

  /**
   * Returns a list of all workflows to add the permissions for.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|\Drupal\workflows\Entity\Workflow[]
   */
  public static function getWorkflows() {
    return Workflow::loadMultiple();
  }

  public static function getWorkflowStates(Workflow $workflow) {
    return $workflow->get('type_settings')['states'];
  }

  public static function getPermissionName($operation, $workflowId, $moderationStateId) {
    return "workflow $workflowId - {$operation} entities in $moderationStateId state";
  }

  public function getPermissions() {
    $permissions = [];

    /** @var Workflow $workflow */
    foreach (self::getWorkflows() as $workflowId => $workflow) {
      foreach (self::getWorkflowStates($workflow) as $moderationStateId => $state) {
        foreach (self::$OPERATIONS as $operation) {
          $permissions[self::getPermissionName($operation, $workflowId, $moderationStateId)] = [
            'title' => $this->t('Workflow: @workflow - @op entities in the @state state', [
              '@op' => $this->t($operation),
              '@state' => $state['label'],
              '@workflow' => $workflow->label(),
            ]),
            'description' => '',
          ];
        }
      }
    }

    return $permissions;
  }

}

// $permissions[$permission] = [
//        'title' => $this->t('Use the <a href="@url">@label</a> text format', ['@url' => $format->url(), '@label' => $format->label()]),
//        'description' => String::placeholder($this->t('Warning: This permission may have security implications depending on how the text format is configured.')),
//      ];

// Check hook_entity_access

// Add a permission for editing entities in each of the moderation states.

// Implement permission checking.
