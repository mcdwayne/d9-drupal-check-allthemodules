<?php

namespace Drupal\sir_trevor;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ComplexDataValueProcessingEvent
 * @package Drupal\sir_trevor
 */
class ComplexDataValueProcessingEvent extends Event {
  /** @var \stdClass */
  private $dataValue;
  /** @var array */
  private $processedData = [];
  /** @var string */
  private $type;
  /** @var mixed */
  private $replacementValue;

  /**
   * ComplexDataValueProcessingEvent constructor.
   *
   * @param string $type
   * @param \stdClass $dataValue
   */
  public function __construct($type, $dataValue) {
    $this->dataValue = $dataValue;
    $this->type = $type;
  }

  /**
   * @return \stdClass
   */
  public function getDataValue() {
    return $this->dataValue;
  }

  /**
   * @param string $key
   *   The property key on which to set the processed data.
   * @param mixed $processedData
   *   The data to set.
   */
  public function setProcessedData($key, $processedData){
    $this->processedData[$key] = $processedData;
  }

  /**
   * @return bool
   */
  public function hasProcessedData() {
    return !empty($this->processedData);
  }

  /**
   * @return array
   *   Array of processed data keyed by the property on which to set it.
   */
  public function getProcessedData() {
    return $this->processedData;
  }

  /**
   * @return string
   */
  public function getType() {
    return $this->type;
  }

  /**
   * @param mixed $newValue
   */
  public function setReplacementValue($newValue) {
    $this->replacementValue = $newValue;
  }

  /**
   * @return bool
   */
  public function hasReplacementValue() {
    return isset($this->replacementValue);
  }

  /**
   * @return mixed
   */
  public function getReplacementValue() {
    return $this->replacementValue;
  }
}
