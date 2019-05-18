<?php

namespace Drupal\chatbot\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class for plugins wishing to support chatbot.
 */
abstract class ChatbotPluginBase extends PluginBase implements ContainerFactoryPluginInterface, ChatbotPluginInterface {
  use \Drupal\chatbot\Bot\BotTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
