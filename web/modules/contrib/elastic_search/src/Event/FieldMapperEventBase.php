<?php

namespace Drupal\elastic_search\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class FieldMapperEventBase
 *
 * @package Drupal\elastic_search\Event
 */
abstract class FieldMapperEventBase extends Event {

  /**
   * @var string
   */
  protected $id;

  /**
   * @var array
   */
  protected $supported;

  /**
   * FieldMapperEventBase constructor.
   *
   * @param string $id
   * @param array  $supported
   */
  public function __construct(string $id, array $supported) {
    $this->id = $id;
    $this->supported = $supported;
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Getter for the config object.
   *
   * @return array
   */
  public function getSupported() {
    return $this->supported;
  }

  /**
   * Setter for the config object.
   *
   * @param array $supported
   */
  public function setSupported(array $supported) {
    $this->supported = $supported;
  }

}