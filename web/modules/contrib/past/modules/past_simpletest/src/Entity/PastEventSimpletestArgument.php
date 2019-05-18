<?php

use Drupal\Component\Serialization\Json;
use Drupal\past\PastEventArgumentInterface;

/**
 * The Arguments Entity class for the Past Simpletest backend.
 */
class PastEventSimpletestArgument implements PastEventArgumentInterface {

  public $argument_id;
  public $event_id;
  protected $original_data;
  public $name;
  public $type;
  public $raw;

  public function __construct(array $values = []) {
    foreach ($values as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->original_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->raw;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setRaw($data, $json_encode = TRUE) {
    $this->raw = $json_encode ? Json::encode($data) : $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return $this->original_data;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureType() {
    if (isset($this->original_data)) {
      if (is_object($this->original_data)) {
        $this->type = get_class($this->original_data);
      }
      else {
        $this->type = gettype($this->original_data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultLabel() {
    return $this->getKey();
  }
}
