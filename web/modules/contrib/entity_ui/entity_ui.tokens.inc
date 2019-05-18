<?php

/**
 * @file
 * Contains token hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function entity_ui_token_info() {
  $type = [
    'name' => t('Entity UI tab'),
    'description' => t('Entity UI tab.'),
    // TODO: also needs entity_ui_target_entity, but we can only specify one!
    'needs-data' => 'entity_ui_tab',
  ];

  $node['target_entity'] = [
    'name' => t("Target entity"),
    'description' => t("The entity that the entity tab is displayed on."),
  ];

  return [
    'types' => ['entity_ui_tab' => $type],
    'tokens' => ['entity_ui_tab' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function entity_ui_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($target_entity_tokens = $token_service->findWithPrefix($tokens, 'target_entity')) {
    $target_entity_type_id = $data['entity_ui_target_entity']->getEntityTypeId();

    $replacements += $token_service->generate($target_entity_type_id, $target_entity_tokens, [$target_entity_type_id => $data['entity_ui_target_entity']], $options, $bubbleable_metadata);
  }

  return $replacements;
}
