<?php

/**
 * @file
 * Sends events related to comments to Mixpanel.
 */

use \Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_comment_insert().
 */
function mixpanel_defaults_comment_insert(EntityInterface $comment) {

  $node = $comment->entity_id->entity;

  $values = array(
    'node-id' => $node->id(),
    'node-title' => $node->label(),
    'node-type' => $node->bundle(),
    'comment-id' => $comment->id(),
  );

  // @todo: how does Organic Groups do this now?
  if (!empty($node->og_groups)) {
    $values['group-id'] = array_pop(array_values($node->og_groups));
  }

  mixpanel_track("comment-created", $values);
}
