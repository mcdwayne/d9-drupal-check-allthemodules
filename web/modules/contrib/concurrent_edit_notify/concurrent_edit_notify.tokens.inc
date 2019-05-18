<?php

/**
 * @file
 * Token integration for the concurrent edit notify module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_token_info_alter().
 */
function concurrent_edit_notify_token_info_alter(&$data) {
  $data['tokens']['node']['first_author'] = [
    'name' => t('First Author'),
    'description' => t('First author among those are concurrently editing a node.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function concurrent_edit_notify_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if (($type == 'node') && !empty($data['node']) && ($data['node']->isTranslatable())) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first_author':
          $concurrent_token = Drupal::service('concurrent_edit_notify.concurrent_token');
          $first_token = $concurrent_token->loadFirst($node->id(), $node->get('langcode')->value);
          $user = \Drupal::entityTypeManager()->getStorage('user')->load($first_token->uid);
          if (($user instanceof EntityInterface) && $user->hasField('name')) {
            $replacements[$original] = $user->get('name')->value;
          }
          break;
      }
    }
  }

  return $replacements;
}
