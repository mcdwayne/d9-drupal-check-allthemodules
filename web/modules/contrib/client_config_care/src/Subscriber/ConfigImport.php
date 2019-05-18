<?php

namespace Drupal\client_config_care\Subscriber;

use Drupal\client_config_care\LogMessageStorage;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class ConfigImport implements EventSubscriberInterface {

  /**
   * @var LoggerChannelInterface
   */
  private $logger;

  public function __construct(LoggerChannelInterface $logger) {
    $this->logger = $logger;
  }

  public function onConfigImport(ConfigImporterEvent $event) {
    if (LogMessageStorage::hasMessage()) {
      foreach (LogMessageStorage::getMessages() as $configName => $message) {
        $this->logger->notice($message);
        LogMessageStorage::removeMessage($configName);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT][] = ['onConfigImport', 0];
    return $events;
  }

}
