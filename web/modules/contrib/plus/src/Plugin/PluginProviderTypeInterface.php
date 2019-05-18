<?php

namespace Drupal\plus\Plugin;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Interface PluginProviderTypeInterface.
 */
interface PluginProviderTypeInterface extends CacheableDependencyInterface, ContainerAwareInterface, ContainerInjectionInterface {

  /**
   * Invokes the hook to alter the definitions if the alter hook is set.
   *
   * @param string $hook
   *   The alter hook to invoke.
   * @param array $definitions
   *   The discovered plugin definitions.
   */
  public function alterDefinitions($hook, array &$definitions);

  /**
   * Determines if the provider of a definition exists.
   *
   * @param string $provider
   *   The machine name of the provider.
   *
   * @return bool
   *   TRUE if provider exists, FALSE otherwise.
   */
  public function providerExists($provider);

  /**
   * Retrieves namespaces for the provider.
   *
   * @param string $name
   *   (optional) A specific extension to limit namespaces to. If not set, all
   *   extension namespaces will be used.
   * @param string $type
   *   (optional) The provider type used to limit namespaces. This is only
   *   really useful when the provider type is actually a chain of providers.
   *
   * @return \Drupal\plus\Utility\ArrayObject
   *   An iterable list of namespace => path key/value pairs.
   */
  public function getNamespaces($name = NULL, $type = NULL);

  /**
   * Retrieves the provider type.
   *
   * @return mixed
   *   The provider type.
   */
  public function getType();

}
