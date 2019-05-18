<?php

namespace Drupal\inmail;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\inmail\Entity\DelivererConfig;

/**
 * The processor result collects outcomes of a single mail processing pass.
 *
 * @ingroup processing
 */
interface ProcessorResultInterface {

  /**
   * Set the deliverer of the message to which this result applies.
   *
   * @param \Drupal\inmail\Entity\DelivererConfig $deliverer
   *   The deliverer config entity.
   */
  public function setDeliverer(DelivererConfig $deliverer);

  /**
   * Get the deliverer of the message to which this result applies.
   *
   * @return \Drupal\inmail\Entity\DelivererConfig
   *   The deliverer config entity.
   */
  public function getDeliverer();

  /**
   * Returns an analyzer result instance, after first creating it if needed.
   *
   * If a result object has already been created with the given topic name, that
   * object will be used.
   *
   * @param string $topic
   *   An identifier for the analyzer result object.
   * @param callable $factory
   *   A function that returns an analyzer result object. This will be called if
   *   there is no object previously created for the given topic name.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface
   *   The analyzer result object.
   *
   * @throws \InvalidArgumentException
   *   If the callable returns something else than an analyzer result object.
   */
  public function ensureAnalyzerResult($topic, callable $factory);

  /**
   * Returns an analyzer result instance.
   *
   * @param string $topic
   *   (optional) The topic identifier for the analyzer result object.
   *   Default to the default analyzer result's topic.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface
   *   The analyzer result object. If no result object has yet been added for
   *   the given key, this returns NULL.
   */
  public function getAnalyzerResult($topic = DefaultAnalyzerResult::TOPIC);

  /**
   * Returns all analyzer results.
   *
   * @return \Drupal\inmail\AnalyzerResultInterface[]
   *   A list of analyzer results.
   */
  public function getAnalyzerResults();

  /**
   * Add a log message to the processing logger.
   *
   * @param string $source
   *   The name of the analyzer or handler that produced the message.
   * @param string $message
   *   The log message.
   * @param array $placeholders
   *   Placeholder substitution map.
   * @param RfcLogLevel $severity
   *   (optional) The severity of the message. Defaults to 'RfcLogLevel::NOTICE'.
   */
  public function log($source, $message, array $placeholders = array(), $severity = RfcLogLevel::NOTICE);

  /**
   * Returns the log messages.
   *
   * This method must not be used by analyzers nor handlers. To make handlers
   * dependent on analyzer result types, use a dedicated class that implements
   * \Drupal\inmail\AnalyzerResultInterface.
   *
   * @param RfcLogLevel $max_severity
   *   (optional) Maximum log message severity. Defaults to 'RfcLogLevel::INFO'.
   *
   * @return array
   *   A list of log items, each an associative array containing:
   *     - message: The log message.
   *     - placeholders: Placeholder substitution map.
   */
  public function readLog($max_severity = RfcLogLevel::INFO);

  /**
   * Sets the flag of processing to TRUE for specific message.
   *
   * @param string $key
   *   Key of the message.
   */
  public function success($key);

  /**
   * Returns TRUE if message processing was successful.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function isSuccess();

}
