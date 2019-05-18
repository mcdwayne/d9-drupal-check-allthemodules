<?php

namespace Drupal\message_notify_slack\Plugin\Notifier;

use Drupal\message\MessageInterface;
use Drupal\message_notify\Plugin\Notifier\MessageNotifierBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slack notifier.
 *
 * @Notifier(
 *   id = "slack",
 *   title = @Translation("Slack"),
 *   description = @Translation("Send messages via Slack"),
 *   viewModes = {
 *     "message"
 *   }
 * )
 */
class Slack extends MessageNotifierBase {

  /**
   * @var \Drupal\slack\Slack
   */
  protected $slack;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\Core\Logger\LoggerChannelInterface $logger, \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager, \Drupal\Core\Render\RendererInterface $renderer, \Drupal\message\MessageInterface $message, \Drupal\slack\Slack $slack) {
    // Set configuration defaults.
    $configuration += [
      'channel' => NULL,
      'username' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $entity_type_manager, $renderer, $message);
    $this->slack = $slack;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MessageInterface $message = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.message_notify'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $message,
      $container->get('slack.slack_service')
    );
  }

  public function deliver(array $output = []) {
    // Remove HTML tags. @TODO Consider whether it might be worth somehow converting HTML to Slack syntax?
    $message = trim(strip_tags($output['message']));
    $result = $this->slack->sendMessage($message, $this->configuration['channel'], $this->configuration['username']);
    if (FALSE === $result) {
      return FALSE;
    }
    return TRUE;
  }

}