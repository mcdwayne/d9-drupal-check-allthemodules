<?php

/**
 * @file
 * Hooks provided by the Private Conversation module.
 */

use Drupal\ptalk\MessageInterface;
use Drupal\ptalk\ThreadInterface;
use Drupal\Core\Url;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the links of a message.
 *
 * @param array &$links
 *   A renderable array representing the message links.
 * @param \Drupal\ptalk\MessageInterface $entity
 *   The message being rendered.
 * @param array &$context
 *   The view mode in which the message is being viewed.
 *
 * @see \Drupal\ptalk\MessageViewBuilder::renderLinks()
 * @see \Drupal\ptalk\MessageViewBuilder::buildLinks()
 */
function hook_ptalk_message_links_alter(array &$links, MessageInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__ptalk_message__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'message-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('message_test.report', ['message' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("private/message/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * Alter the links of a thread.
 *
 * @param array &$links
 *   A renderable array representing the thread links.
 * @param \Drupal\ptalk\ThreadInterface $entity
 *   The thread being rendered.
 * @param array &$context
 *   The view mode in which the thread is being viewed.
 *
 * @see \Drupal\ptalk\ThreadViewBuilder::renderLinks()
 * @see \Drupal\ptalk\ThreadViewBuilder::buildLinks()
 */
function hook_ptalk_thread_links_alter(array &$links, ThreadInterface $entity, array &$context) {
  $links['mymodule'] = [
    '#theme' => 'links__ptalk_thread__mymodule',
    '#attributes' => ['class' => ['links', 'inline']],
    '#links' => [
      'thread-report' => [
        'title' => t('Report'),
        'url' => Url::fromRoute('thread_test.report', ['thread' => $entity->id()], ['query' => ['token' => \Drupal::getContainer()->get('csrf_token')->get("private/conversation/{$entity->id()}/report")]]),
      ],
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
