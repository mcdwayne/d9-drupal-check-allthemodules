<?php

namespace Drupal\authorization_code\Plugin\UserIdentifier;

use Drupal\authorization_code\Exceptions\BrokenPluginException;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\authorization_code\UserIdentifierInterface;

/**
 * Broken implementation of user identifier plugin.
 *
 * @UserIdentifier(
 *   id = "broken",
 *   title = @Translation("Broken / Missing")
 * )
 */
class Broken extends AuthorizationCodePluginBase implements UserIdentifierInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\authorization_code\Exceptions\BrokenPluginException
   */
  public function loadUser($identifier) {
    throw new BrokenPluginException('user_identifier', $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function isBroken(): bool {
    return TRUE;
  }

}
