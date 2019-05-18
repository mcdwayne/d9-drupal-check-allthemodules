<?php

namespace Drupal\cleaner\EventSubscriber;

use Drupal\cleaner\Event\CleanerRunEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CleanerWatchdogClearEventSubscriber.
 *
 * @package Drupal\cleaner\EventSubscriber
 */
class CleanerWatchdogClearEventSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CleanerRunEvent::CLEANER_RUN][] = ['clearWatchdog', 100];
    return $events;
  }

  /**
   * CleanerWatchdogClearEventSubscriber constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger channel factory.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_channel_factory
  ) {
    $this->database      = $database;
    $this->config        = $config_factory->get('cleaner.settings');
    $this->loggerChannel = $logger_channel_factory->get('cleaner');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Cleaner tables clearing.
   */
  public function clearWatchdog() {
    if ($this->config->get('cleaner_empty_watchdog')) {
      if (!$this->database->schema()->tableExists('watchdog')) {
        $this->loggerChannel
          ->error("Something going wrong - watchdog logs cannot be cleared.");
      }
      else {
        $this->database->query('TRUNCATE {watchdog}')
          ->execute();
        $this->loggerChannel
          ->info('Watchdog logs has been successfully cleared.');
      }
    }
  }

}
