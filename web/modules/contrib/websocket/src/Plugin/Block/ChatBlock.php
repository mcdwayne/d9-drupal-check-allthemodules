<?php

namespace Drupal\websocket\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Chat block.
 *
 * @Block(
 *   id = "chat_block",
 *   admin_label = @Translation("Chat block"),
 * )
 */
class ChatBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => 'Chat block',
      '#theme' => 'chat_block',
      '#form' => \Drupal::formBuilder()->getForm('\Drupal\websocket\Form\ChatForm'),
      '#attached' => [
        'library' => [
          'websocket/chat',
        ],
      ],
    ];
  }

}
