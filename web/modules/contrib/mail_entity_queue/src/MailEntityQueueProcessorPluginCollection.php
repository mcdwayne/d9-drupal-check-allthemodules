<?php

namespace Drupal\mail_entity_queue;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of mail entity queue processor plugins.
 */
class MailEntityQueueProcessorPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The mail queue processor's ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $mailEntityQueueId;

  /**
   * Constructs a new MailEntityQueueProcessorPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param string $mail_entity_queue_id
   *   The unique ID of the mail queue entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, $mail_entity_queue_id) {
    parent::__construct($manager, $instance_id, []);

    $this->mailEntityQueueId = $mail_entity_queue_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    try {
      if (!$instance_id) {
        throw new PluginException("The mail entity queue '{$this->mailEntityQueueId}' did not specify a plugin.");
      }

      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      throw $e;
    }
  }

}
