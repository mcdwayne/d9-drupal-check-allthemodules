<?php

namespace Drupal\client_connection\Plugin\ClientConnection;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines an interface for Client Connection plugins.
 */
interface ClientConnectionInterface extends CacheableDependencyInterface, ConfigurablePluginInterface, ContextAwarePluginInterface, PluginFormInterface, PluginWithFormsInterface {

  /**
   * Gets the user-facing client label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The user-facing client label.
   */
  public function label();

  /**
   * Gets the user-facing client description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The user-facing client description.
   */
  public function description();

  /**
   * Gets the user-facing client categories.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of user-facing client categories.
   */
  public function categories();

  /**
   * Gets a particular value in the client settings.
   *
   * @param array|string $key
   *   The key or array of keys to find in the configuration array.
   * @param mixed $default_return
   *   The default fallback return if the value is unset.
   *
   * @return mixed
   *   The configuration value.
   */
  public function getConfigurationValue($key, $default_return = NULL);

  /**
   * Sets a particular value in the client settings.
   *
   * @param string $key
   *   The key of PluginBase::$configuration to set.
   * @param mixed $value
   *   The value to set for the provided key.
   *
   * @todo This doesn't belong here. Move this into a new base class in
   *   https://www.drupal.org/node/1764380.
   * @todo This does not set a value in \Drupal::config(), so the name is confusing.
   *
   * @see \Drupal\Component\Plugin\PluginBase::$configuration
   */
  public function setConfigurationValue($key, $value);

}
