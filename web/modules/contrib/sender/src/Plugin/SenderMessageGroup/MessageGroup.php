<?php

namespace Drupal\sender\Plugin\SenderMessageGroup;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a message group.
 */
class MessageGroup extends PluginBase implements MessageGroupInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->t($this->pluginDefinition['label']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenTypes() {
    return $this->pluginDefinition['token_types'];
  }

}
