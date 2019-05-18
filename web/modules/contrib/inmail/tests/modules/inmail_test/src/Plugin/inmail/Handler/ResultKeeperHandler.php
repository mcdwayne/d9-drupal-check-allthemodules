<?php

namespace Drupal\inmail_test\Plugin\inmail\Handler;

use Drupal\inmail\MIME\MimeMessageInterface;
use Drupal\inmail\Plugin\inmail\Handler\HandlerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * Stores analysis results to let them be easily evaluated by tests.
 *
 * @Handler(
 *   id = "result_keeper",
 *   label = @Translation("Result keeper"),
 *   description = @Translation("Stores analysis results to let them be easily evaluated by tests.")
 * )
 */
class ResultKeeperHandler extends HandlerBase {

  /**
   * {@inheritdoc}
   */
  public function help() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function invoke(MimeMessageInterface $message, ProcessorResultInterface $processor_result) {
    \Drupal::state()->set('inmail_test.result_keeper.message', $message);
    \Drupal::state()->set('inmail_test.result_keeper.result', $processor_result);
    \Drupal::state()->set('inmail_test.result_keeper.account_name', \Drupal::currentUser()->getDisplayName());
  }

  /**
   * Returns the latest message processed by this handler.
   *
   * @return \Drupal\inmail\MIME\MimeMessageInterface|null
   *   The latest message object, or NULL if none has been handled.
   */
  public static function getMessage() {
    return \Drupal::state()->get('inmail_test.result_keeper.message');
  }

  /**
   * Returns the latest processing result.
   *
   * @return \Drupal\inmail\ProcessorResultInterface|null
   *   The latest processing result.
   */
  public static function getResult() {
    return \Drupal::state()->get('inmail_test.result_keeper.result');
  }

  /**
   * Returns the account display name.
   *
   * @return string
   *   The account display name.
   */
  public static function getAccountName() {
    return \Drupal::state()->get('inmail_test.result_keeper.account_name');
  }

}
