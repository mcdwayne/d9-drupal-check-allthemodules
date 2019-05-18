<?php

namespace Drupal\entity_role_view_mode_switcher\Util;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Class ViewModeSwitcher.
 *
 * @package Drupal\entity_role_view_mode_switcher\Util
 */
class ViewModeSwitcher {

  /**
   * Switch entity's view mode based on current user's role and the rules set.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $viewMode
   *   The current view mode.
   * @param array $roles
   *   The current user's roles.
   *
   * @return string
   *   The view mode to show.
   */
  public static function switchViewModes(EntityInterface $entity, string $viewMode, array $roles) {
    // We can't have our field on an entity that does not support fields.
    if (!$entity instanceof FieldableEntityInterface) {
      return $viewMode;
    }

    // Find the field that has the reference, if it exists.
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fieldDefinitions */
    $fieldDefinitions = $entity->getFieldDefinitions();
    $viewModeRoleRuleFieldName = NULL;
    foreach ($fieldDefinitions as $fieldDefinition) {
      if ($fieldDefinition->getType() === 'entity_reference') {
        $targetSetting = $fieldDefinition->getSetting('target_type');

        if ($targetSetting === 'rule') {
          $viewModeRoleRuleFieldName = $fieldDefinition->getName();
          // Only support one field so breaking here.
          break;
        }
      }
    }

    if (!$viewModeRoleRuleFieldName) {
      return $viewMode;
    }

    $entityType = $entity->getEntityType()->id();
    $qualifiedOriginalViewMode = $entityType . '.' . $viewMode;

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $viewModeRoleRuleField */
    $viewModeRoleRuleField = $entity->get($viewModeRoleRuleFieldName);
    /** @var \Drupal\entity_role_view_mode_switcher\Entity\RuleInterface[] $rules */
    $rules = $viewModeRoleRuleField->referencedEntities();

    foreach ($rules as $rule) {
      $conditions = $rule->getConditions();
      foreach ($conditions as $condition) {
        if ($condition['original_view_mode_id'] === $qualifiedOriginalViewMode
          && (($condition['negate'] && !\in_array($condition['role_id'], $roles, TRUE))
            || (!$condition['negate'] && \in_array($condition['role_id'], $roles, TRUE)))) {
          list($type, $viewMode) = explode('.', $condition['new_view_mode_id']);
          // The first ones take precedence.
          break;
        }
      }
    }

    return $viewMode;
  }

}
