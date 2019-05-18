<?php

namespace Drupal\developer_suite;

/**
 * Class BatchManager.
 *
 * @package Drupal\developer_suite
 */
class BatchManager implements BatchManagerInterface {

  /**
   * The batch title.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $title;

  /**
   * The initial message.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $initialMessage;

  /**
   * The progress message.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $progressMessage;

  /**
   * The error message.
   *
   * @var string|\Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $errorMessage;

  /**
   * The finished callback.
   *
   * @var string|array
   */
  private $finishedCallback;

  /**
   * The operations.
   *
   * @var array
   */
  private $operations = [];

  /**
   * Sets the title.
   *
   * @param string $title
   *   The title.
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;

    return $this;
  }

  /**
   * Sets the initial message.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $initialMessage
   *   The initial message.
   *
   * @return $this
   */
  public function setInitialMessage($initialMessage) {
    $this->initialMessage = $initialMessage;

    return $this;
  }

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
  public function setProgressMessage($progressMessage) {
    $this->progressMessage = $progressMessage;

    return $this;
  }

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
  public function setFinishedCallback($finishedCallback) {
    $this->finishedCallback = $finishedCallback;

    return $this;
  }

  /**
   * Sets the error message.
   *
   * @param string|\Drupal\Core\StringTranslation\TranslatableMarkup $errorMessage
   *   The error message.
   *
   * @return $this
   */
  public function setErrorMessage($errorMessage) {
    $this->errorMessage = $errorMessage;

    return $this;
  }

  /**
   * Builds the batch array and sets it for processing.
   */
  public function execute() {
    // If the title is provided, set the title.
    if ($this->title) {
      $batch['title'] = $this->title;
    }
    // If the initial message is provided, set the initial message.
    if ($this->initialMessage) {
      $batch['init_message'] = $this->initialMessage;
    }
    // If the progress message is provided, set the progress message.
    if ($this->progressMessage) {
      $batch['progress_message'] = $this->progressMessage;
    }
    // If the error message is provided, set the error message.
    if ($this->errorMessage) {
      $batch['error_message'] = $this->errorMessage;
    }
    // If a finished callback is provided, set the finished callback.
    if ($this->finishedCallback) {
      $batch['finished'] = $this->finishedCallback;
    }

    // Set the operations.
    $batch['operations'] = $this->operations;

    // Set the batch.
    batch_set($batch);
  }

  /**
   * Processes the batch.
   *
   * Only call this method when the batch is not called from a form submit
   * handler.
   *
   * @param string|\Drupal\Core\Url $uri
   *   The redirect URI.
   */
  public function process($uri) {
    batch_process($uri);
  }

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
  public function addOperation($operation, array $parameters) {
    $this->operations[] = [$operation, $parameters];

    return $this;
  }

}
