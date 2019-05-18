<?php

namespace Drupal\authorization_code\Plugin\CodeSender;

use Drupal\authorization_code\CodeSenderInterface;
use Drupal\authorization_code\Exceptions\BrokenPluginException;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\user\UserInterface;

/**
 * Broken implementation of code sender plugin.
 *
 * @CodeSender(
 *   id = "broken",
 *   title = @Translation("Broken / Missing")
 * )
 */
class Broken extends AuthorizationCodePluginBase implements CodeSenderInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\authorization_code\Exceptions\BrokenPluginException
   */
  public function sendCode(UserInterface $user, string $code) {
    throw new BrokenPluginException('code_sender', $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function isBroken(): bool {
    return TRUE;
  }

}
