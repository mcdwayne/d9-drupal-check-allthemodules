<?php

namespace Drupal\authorization_code_login_process_test\Plugin\CodeSender;

use Drupal\authorization_code\CodeSenderInterface;
use Drupal\authorization_code\Plugin\CodeSender\CodeSenderBase;
use Drupal\user\UserInterface;

/**
 * A code sender implementation that does not send the code.
 *
 * @CodeSender(
 *   id = "ignore",
 *   title = @Translation("Ignore")
 * )
 */
class Ignore extends CodeSenderBase implements CodeSenderInterface {

  /**
   * {@inheritdoc}
   */
  public function sendCode(UserInterface $user, string $code) {
    // Do nothing.
  }

}
