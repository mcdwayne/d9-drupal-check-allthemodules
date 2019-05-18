<?php

namespace Drupal\chatbot\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a container for lazily loading chatbot plugins.
 */
class ChatbotPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The unique ID for the chatbot using this plugin collection.
   *
   * @var string
   */
  protected $chatbotId;

  /**
   * Constructs a new ChatbotPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $chatbot_id
   *   The unique ID of the chatbot using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $chatbot_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->chatbotId = $chatbot_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\chatbot\Plugin\ChatbotPluginInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);
  }

}
