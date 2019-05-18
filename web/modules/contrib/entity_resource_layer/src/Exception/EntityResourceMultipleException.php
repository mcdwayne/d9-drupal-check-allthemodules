<?php

namespace Drupal\entity_resource_layer\Exception;

/**
 * Class EntityResourceMultipleException.
 *
 * @package Drupal\entity_resource_layer\Exception
 */
class EntityResourceMultipleException extends EntityResourceException {

  /**
   * List of exceptions.
   *
   * @var \Exception[]
   */
  protected $exceptions = [];

  /**
   * Add an exception.
   *
   * @param \Exception $exception
   *   The exception to add.
   */
  public function addException(\Exception $exception) {
    $this->exceptions[] = $exception;
  }

  /**
   * Checks whether there are exceptions.
   *
   * @return bool
   *   Are there exceptions.
   */
  public function hasException() {
    return (bool) count($this->exceptions);
  }

  /**
   * Get the exceptions.
   *
   * @return \Exception[]
   *   Exceptions.
   */
  public function getExceptions() {
    return $this->exceptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceData($includeCode = FALSE) {
    $errors = [];

    foreach ($this->exceptions as $exception) {
      if ($exception instanceof EntityResourceException) {
        $errors[] = $exception->getResourceData(TRUE);
      }
      else {
        $errors[] = ['message' => $exception->getMessage()];
      }
    }

    return parent::getResourceData($includeCode)
      + ['errors' => $errors];
  }

  /**
   * Adds exceptions from another multiple exception.
   *
   * @param \Drupal\entity_resource_layer\Exception\EntityResourceMultipleException $exception
   *   The other exception.
   *
   * @return $this
   */
  public function addFrom(EntityResourceMultipleException $exception) {
    $this->exceptions = array_merge($this->exceptions, $exception->getExceptions());
    return $this;
  }

}
