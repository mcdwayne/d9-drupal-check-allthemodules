<?php

namespace Drupal\cleaner\EventSubscriber;

use Drupal\cleaner\Event\CleanerRunEvent;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CleanerMysqlOptimizeEventSubscriber.
 *
 * @package Drupal\cleaner\EventSubscriber
 */
class CleanerMysqlOptimizeEventSubscriber implements EventSubscriberInterface, ContainerInjectionInterface {

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
    $events[CleanerRunEvent::CLEANER_RUN][] = ['optimizeMysql', 100];
    return $events;
  }

  /**
   * CleanerMysqlOptimizeEventSubscriber constructor.
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
   * Cleaner MySQL optimization.
   */
  public function optimizeMysql() {
    $opt = $this->config->get('cleaner_optimize_db');
    if ($opt) {
      // Get's the database driver name.
      $db_type = $this->database->driver();
      // Make sure the db type hasn't changed.
      if ($db_type == 'mysql') {
        // Gathering tables list.
        $list = $this->buildTablesList();
        if (!empty($list)) {
          // Run optimization timer.
          Timer::start('cleaner_db_optimization');
          // Perform optimization.
          $this->optimizeIt(static::getOptimizationQuery($opt, $list));
          // Write a log about successful optimization into the watchdog.
          // Convert tables list into a comma-separated list.
          $list = implode(', ', $list);
          // Get the timers's results.
          $time = static::getTimerResult();
          $this->loggerChannel
            ->info(
              "Optimized tables: @list. This required @time seconds.",
              ['@list' => $list, '@time' => $time]
            );
        }
        else {
          // Write a log about thing that optimization process is
          // no tables which can to be optimized.
          $this->loggerChannel
            ->info('There is no tables which can to be optimized.');
        }
      }
      else {
        // Write a log(error) about thing that optimization process
        // isn't allowed for non-MySQL databases into the watchdog.
        // Change log level to an error.
        $this->loggerChannel
          ->error(
            "Database type (@db_type) not allowed to be optimized.",
            ['@db_type' => $db_type]
          );
      }
    }
  }

  /**
   * Extract and format the timer results.
   *
   * @return string
   *   Formatted timer results.
   */
  private static function getTimerResult() {
    // Get raw timer's data in milliseconds.
    $raw = Timer::read('cleaner_db_optimization');
    // Convert it to seconds.
    $raw /= 1000;
    // Convert it to the correct number format.
    return number_format($raw, 3);
  }

  /**
   * Perform the optimization query.
   *
   * @param string $query
   *   Query string.
   */
  protected function optimizeIt($query) {
    $this->database->query((string) $query)->execute();
  }

  /**
   * Build the optimization query string.
   *
   * @param int $opt
   *   Operation flag.
   * @param array $list
   *   Tables list array.
   *
   * @return string
   *   Optimization query string.
   */
  protected static function getOptimizationQuery($opt, $list) {
    $query = 'OPTIMIZE ' . ($opt == 2 ? 'LOCAL ' : '');
    $query .= 'TABLE {' . (implode('}, {', $list)) . '}';
    return $query;
  }

  /**
   * Build the tables list.
   *
   * @return array Tables list array.
   *   Tables list array.
   */
  protected function buildTablesList() {
    $list = [];
    $tables = (array) $this->database->query("SHOW TABLE STATUS");
    if (!empty($tables)) {
      foreach ($tables as $table) {
        if (isset($table->Data_free) && !empty($table->Data_free)) {
          $list[] = (string) $table->Name;
        }
      }
    }
    return $list;
  }

}
