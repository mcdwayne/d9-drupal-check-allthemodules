<?php

namespace Drupal\open_connect\Plugin\OpenConnect\Provider;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Creates an interface for identity providers.
 */
interface ProviderInterface extends PluginFormInterface, ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Gets the authorize url.
   *
   * @param string $state
   *    The state query parameter to prevent CSRF.
   *
   * @return \Drupal\Core\Url
   *   The authorize url.
   */
  public function getAuthorizeUrl($state);

  /**
   * Authenticates a user with the given code.
   *
   * @param string $code
   *   The authorization code.
   *
   * @return \Drupal\user\UserInterface
   *   The authenticated user.
   *
   * @throws \Drupal\open_connect\Exception\OpenConnectException
   *   Thrown when authentication fails for any reason.
   */
  public function authenticate($code);

}
