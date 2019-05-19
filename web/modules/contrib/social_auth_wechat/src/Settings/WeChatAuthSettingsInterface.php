<?php

namespace Drupal\social_auth_wechat\Settings;

/**
 * Defines an interface for Social Auth WeChat settings.
 */
interface WeChatAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

  /**
   * Gets the client scope.
   *
   * @return string
   *   The client scope.
   */
  public function getClientScope();

}
