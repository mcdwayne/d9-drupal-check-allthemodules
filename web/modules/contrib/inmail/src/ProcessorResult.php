<?php

namespace Drupal\inmail;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\inmail\Entity\DelivererConfig;

/**
 * The processor result collects outcomes of a single mail processing pass.
 *
 * @ingroup processing
 */
class ProcessorResult implements ProcessorResultInterface {

  /**
   * The deliverer of the message to which this result applies.
   *
   * @var \Drupal\inmail\Entity\DelivererConfig
   */
  protected $deliverer;

  /**
   * Instantiated analyzer result objects, keyed by topic.
   *
   * @var \Drupal\inmail\AnalyzerResultInterface[]
   */
  protected $analyzerResults = array();

  /**
   * Logged messages.
   *
   * @var array[][]
   */
  protected $log = array();

  /**
   * Is success.
   *
   * @var bool
   */
  protected $success = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setDeliverer(DelivererConfig $deliverer) {
    $this->deliverer = $deliverer;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeliverer() {
    return $this->deliverer;
  }

  /**
   * Creates a new analyzer result instance.
   */
  public function __construct () {
    $this->analyzerResults[DefaultAnalyzerResult::TOPIC] = new DefaultAnalyzerResult();
  }

  /**
   * {@inheritdoc}
   */
  public function ensureAnalyzerResult($topic, callable $factory) {
    // Create the result object if it does not exist.
    if (!isset($this->analyzerResults[$topic])) {
      $analyzer_result = $factory();
      if (!$analyzer_result instanceof AnalyzerResultInterface) {
        throw new \InvalidArgumentException('Factory callable did not return an AnalyzerResultInterface instance');
      }
      $this->analyzerResults[$topic] = $analyzer_result;
    }

    // Return the result object.
    return $this->analyzerResults[$topic];
  }

  /**
   * {@inheritdoc}
   */
  public function getAnalyzerResult($topic = DefaultAnalyzerResult::TOPIC) {
    if (isset($this->analyzerResults[$topic])) {
      return $this->analyzerResults[$topic];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnalyzerResults() {
    return $this->analyzerResults;
  }

  /**
   * {@inheritdoc}
   */
  public function log($source, $message, array $placeholders = array(), $severity = RfcLogLevel::NOTICE) {
    $this->log[$source][] = [
      'message' => $message,
      'placeholders' => $placeholders,
      'severity' => $severity,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function readLog($max_severity = RfcLogLevel::INFO) {
    $messages = [];
    foreach ($this->log as $source => $log) {
      foreach ($log as $items) {
        if ($items['severity'] <= $max_severity) {
          $messages[$source] = $log;
        }
      }
    }
    return $messages;
  }

  /**
   * {@inheritdoc}
   */
  public function success($key) {
    $deliverer = $this->getDeliverer();
    try {
      $plugin = $deliverer->getPluginInstance();
      $plugin->success($key);
    }
    catch (PluginNotFoundException $e) {
      // There is no plugin.
    }
    $this->success = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSuccess() {
    return $this->success;
  }

}
