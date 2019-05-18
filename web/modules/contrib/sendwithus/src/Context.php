<?php

declare(strict_types = 1);

namespace Drupal\sendwithus;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Provides a context to store required email metadata.
 */
final class Context {

  protected $module;
  protected $key;
  protected $data;

  /**
   * Constructs a new instance.
   *
   * @param string $module
   *   The module sending email.
   * @param string $key
   *   The email key.
   * @param \Symfony\Component\HttpFoundation\ParameterBag $data
   *   The data.
   */
  public function __construct(string $module, string $key, ParameterBag $data) {
    $this->module = $module;
    $this->key = $key;
    $this->data = $data;
  }

  /**
   * Gets the module.
   *
   * @return string
   *   The module.
   */
  public function getModule() : string {
    return $this->module;
  }

  /**
   * Gets the email id.
   *
   * @return string
   *   The id.
   */
  public function getKey() : string {
    return $this->key;
  }

  /**
   * Gets the data.
   *
   * @return \Symfony\Component\HttpFoundation\ParameterBag
   *   The data.
   */
  public function getData() : ParameterBag {
    return $this->data;
  }

}
