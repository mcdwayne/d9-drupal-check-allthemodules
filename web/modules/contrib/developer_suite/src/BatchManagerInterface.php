<?php

namespace Drupal\developer_suite;

/**
 * Class BatchManager.
 *
 * @package Drupal\developer_suite
 */
interface BatchManagerInterface {

  /**
   * Sets the title.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The title.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Sets the finished callback.
   *
   * Set your callback (static method or instance method) as follows:
   *
   * @code
   *   // Static method, static context.
   *   ->setFinishedCallback(static::class . '::myStaticMethod')
   *
   *   // Instance method, object context.
   *   ->setFinishedCallback([$this, 'myMethod'])
   * @endcode
   *
   * @param string|array $finishedCallback
   *   The finished callback.
   *
   * @return $this
   */
  public function setFinishedCallback($finishedCallback);

  /**
   * Sets the progress message.
   *
   * The following placeholders are available:
   * - @current
   * - @remaining
   * - @total
   * - @percentage.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $progressMessage
   *   The progress message.
   *
   * @return $this
   */
  public function setProgressMessage($progressMessage);

  /**
   * Sets the initial message.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $initialMessage
   *   The initial message.
   *
   * @return $this
   */
  public function setInitialMessage($initialMessage);

  /**
   * Sets the error message.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $errorMessage
   *   The error message.
   *
   * @return $this
   */
  public function setErrorMessage($errorMessage);

  /**
   * Processes the batch.
   *
   * Only call this method when the batch is not called from a form submit
   * handler.
   *
   * @param string|\Drupal\Core\Url $uri
   *   The redirect URI.
   */
  public function process($uri);

  /**
   * Executes the operations.
   */
  public function execute();

  /**
   * Adds an operation to be executed.
   *
   * Set your operation (static method or instance method) as follows:
   *
   * @code
   *   // Static method.
   *   ->addOperation(static::class . '::myStaticMethod')
   *
   *   // Instance method (object context).
   *   ->addOperation([$this, 'myMethod'])
   * @endcode
   *
   * @param string|array $operation
   *   The operation.
   * @param array $parameters
   *   The function parameters.
   *
   * @return $this
   */
  public function addOperation($operation, array $parameters);

}
