<?php

namespace Drupal\livechat\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\livechat\LivechatWidgetHandler;

/**
 * Provides a block to contain the widget code.
 *
 * @Block(
 *   id = "LiveChatBlock",
 *   admin_label = @Translation("RocketChat Livechat"),
 *   category = @Translation("RocketChat"),
 * )
 */
class LiveChatBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $livechatWidget = new LivechatWidgetHandler('livechat', 'rocket_chat_conf');
    $block = $livechatWidget->renderWidgetWithJavaScriptKeys(['server']);
    $block['#cache'] = ['max-age' => 0];
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

}
