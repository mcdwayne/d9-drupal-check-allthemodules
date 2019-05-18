<?php

/**
 * @file
 * Token integration for the Content moderation edit notify module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_token_info_alter().
 */
function content_moderation_edit_notify_token_info_alter(&$data) {
  $data['tokens']['node']['latest_revision_log'] = [
    'name' => t('Latest revision log'),
    'description' => t('The latest node revision log message.'),
  ];
  $data['tokens']['node']['latest_revision_author'] = [
    'name' => t('Latest revision author'),
    'description' => t('The latest node revision author display name.'),
  ];
  $data['tokens']['node']['latest_revision_created'] = [
    'name' => t('Latest revision created date'),
    'description' => t('The latest node revision created date.'),
  ];
  $data['tokens']['node']['latest_revision_url'] = [
    'name' => t('Latest revision url'),
    'description' => t('The latest node revision raw url.'),
  ];
  $data['tokens']['node']['latest_revision_link'] = [
    'name' => t('Latest revision link'),
    'description' => t('The latest node revision full link on word <em>revision</em>.'),
  ];
  $data['tokens']['node']['latest_revision_state'] = [
    'name' => t('Latest revision state'),
    'description' => t('The latest node revision moderation state.'),
  ];
}

/**
 * Implements hook_tokens().
 */
function content_moderation_edit_notify_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];
    $moderation_info = Drupal::service('content_moderation.moderation_information');
    $lastest_revision = $moderation_info->getLatestRevision('node', $node->id());
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'latest_revision_created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($lastest_revision->getRevisionCreationTime());
          break;

        case 'latest_revision_log':
          $log_message = $lastest_revision->getRevisionLogMessage();
          $replacements[$original] = !empty($log_message) ? $log_message : t('This new revision has no log message.');
          break;

        case 'latest_revision_author':
          $replacements[$original] = $lastest_revision->getRevisionUser()->getDisplayName();
          break;

        case 'latest_revision_url':
          $replacements[$original] = Url::fromRoute('entity.node.revision', ['node' => $node->id(), 'node_revision' => $lastest_revision->getRevisionId()], ['attributes' => ['_target' => 'blank']])->toString();
          break;

        case 'latest_revision_link':
          $replacements[$original] = Link::createFromRoute(t('revision'), 'entity.node.revision', ['node' => $node->id(), 'node_revision' => $lastest_revision->getRevisionId()], ['attributes' => ['_target' => 'blank']])->toString();
          break;

        case 'latest_revision_state':
          // Drupal 8.3.x.
          if (isset($lastest_revision->moderation_state->value)) {
            $replacements[$original] = $lastest_revision->moderation_state->value;
          }
          // Drupal 8.2.x.
          elseif (isset($lastest_revision->moderation_state->target_id)) {
            $state_id = $lastest_revision->moderation_state->target_id;
            $replacements[$original] = Drupal\content_moderation\Entity\ModerationState::load($state_id)->label();
          }
          else {
            $replacements[$original] = t('Unknown');
          }
          break;
      }
    }
  }

  return $replacements;
}
