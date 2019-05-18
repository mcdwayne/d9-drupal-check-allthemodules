<?php

namespace Drupal\pubkey_encrypt\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base class for LoginCredentialsProvider plugins.
 */
abstract class LoginCredentialsProviderBase extends PluginBase implements LoginCredentialsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

}
