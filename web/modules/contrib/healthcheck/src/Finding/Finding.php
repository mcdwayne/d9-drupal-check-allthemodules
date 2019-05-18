<?php

namespace Drupal\healthcheck\Finding;

/**
 * Base class for results from Check plugins.
 */
class Finding implements FindingInterface {

  /**
   * The check that discovered this finding.
   *
   * @var \Drupal\healthcheck\Plugin\HealthcheckPluginInterface
   */
  protected $check;

  /**
   * The identifying key for the finding.
   *
   * @var string
   */
  protected $key;

  /**
   * The display label for the finding.
   *
   * @var string
   */
  protected $label = '';

  /**
   * The status of the finding.
   *
   * @var int
   */
  protected $status = FindingStatus::NOT_PERFORMED;

  /**
   * The message to display of the finding.
   *
   * @var string
   */
  protected $message;

  /**
   * Custom data key-value pairs.
   *
   * @var array
   */
  protected $data;

  /**
   * Finding constructor.
   *
   * @param $status
   *   A status from FindingStatus.
   * @param \Drupal\healthcheck\Plugin\HealthcheckPluginInterface $check
   *   The Healthcheck plugin that discovered this finding.
   * @param $key
   *   A unique text key to identify the finding.
   * @param string $message
   *   Optional. A string messages of the finding.
   * @param array $data
   *   Optional. An array of custom key-value pairs.
   */
  public function __construct($status, $check, $key, $message = '', $data = []) {
    $this->key = $key;
    $this->status = $status;
    $this->check = $check;
    $this->message = $message;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheck() {
    return $this->check;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
   return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function addData($key, $value) {
    $this->data[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getData($key) {
    if (isset($this->data[$key])) {
      return $this->data[$key];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllData() {
    return $this->data;
  }

  /**
   * Outputs the finding as an array.
   *
   * @return array
   *   An array representation of the finding.
   */
  public function toArray() {
    return [
      'key' => $this->getKey(),
      'status' => $this->getStatus(),
      'message' => $this->getMessage(),
      'data' => serialize($this->data),
    ];
  }
}
