<?php

namespace Drupal\nimbus;

/**
 * Class ConfigChange.
 *
 * @package Drupal\nimbus
 */
class ConfigChange {

  /**
   * The active config array.
   *
   * @var array
   */
  private $active;

  /**
   * The staging config array.
   *
   * @var array
   */
  private $staging;

  /**
   * ConfigChange constructor.
   *
   * @param array $active
   *   The active config.
   * @param array $staging
   *   The staged config.
   */
  public function __construct(array $active, array $staging) {
    $this->active = $active;
    $this->staging = $staging;
  }

  /**
   * Getter for staging config.
   *
   * @return array
   *   Return staging config array.
   */
  public function getStagingConfig() {
    return $this->staging;
  }

  /**
   * Getter for active config.
   *
   * @return array
   *   Return active config array.
   */
  public function getActiveConfig() {
    return $this->active;
  }

  /**
   * Getter for active uuid.
   *
   * @return bool|string
   *   Return staging uuid or false if nothing exist.
   */
  public function getActiveUuid() {
    if (isset($this->active['uuid'])) {
      return $this->active['uuid'];
    }
    return FALSE;
  }

  /**
   * Getter for staging uuid.
   *
   * @return bool|string
   *   Return staging uuid or false if nothing exist.
   */
  public function getStagingUuid() {
    if (isset($this->staging['uuid'])) {
      return $this->staging['uuid'];
    }
    return FALSE;
  }

  /**
   * Check if uuid not equal.
   *
   * @return bool
   *   Is uuid not equivalent true.
   */
  public function isUuidNotEquivalent() {
    $response = FALSE;
    if ($this->getActiveUuid() && $this->getStagingUuid()) {
      if ($this->getActiveUuid() != $this->getStagingUuid()) {
        $response = TRUE;
      }
    }
    return $response;
  }

}
