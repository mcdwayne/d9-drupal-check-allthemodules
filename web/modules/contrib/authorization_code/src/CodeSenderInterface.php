<?php

namespace Drupal\authorization_code;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\user\UserInterface;

/**
 * The code sender plugin interface.
 */
interface CodeSenderInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Sends the code to the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The code to send.
   *
   * @throws \Drupal\authorization_code\Exceptions\FailedToSendCodeException
   */
  public function sendCode(UserInterface $user, string $code);

}
