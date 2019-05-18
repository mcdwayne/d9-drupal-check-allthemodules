<?php

namespace Drupal\cleaner\EventSubscriber;

use Drupal\cleaner\Event\CleanerRunEvent;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CleanerSessionClearEventSubscriber.
 *
 * @package Drupal\cleaner\EventSubscriber
 */
class CleanerSessionClearEventSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

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
   * Request timestamp.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CleanerRunEvent::CLEANER_RUN][] = ['clearSession', 100];
    return $events;
  }

  /**
   * CleanerSessionClearEventSubscriber constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger channel factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Date time service.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_channel_factory,
    TimeInterface $time
  ) {
    $this->database      = $database;
    $this->config        = $config_factory->get('cleaner.settings');
    $this->loggerChannel = $logger_channel_factory->get('cleaner');
    $this->requestTime   = $time->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * Cleaner session clearing.
   */
  public function clearSession() {
    if ($this->config->get('cleaner_clean_sessions')) {
      $count = $this->database
        ->delete('sessions')
        ->condition('timestamp', $this->getExpirationTime(), '<')
        ->execute();
      if ($count) {
        $this->loggerChannel->info('Old sessions cleared.');
      }
    }
  }

  /**
   * Get the sessions expiration time.
   *
   * @return int
   *   Expiration timestamp.
   */
  protected function getExpirationTime() {
    return (int) ($this->requestTime - session_get_cookie_params()['lifetime']);
  }

}
