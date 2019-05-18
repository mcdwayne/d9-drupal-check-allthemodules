<?php

namespace Drupal\plus\Plugin;

use Drupal\plus\Utility\ArrayObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginProviderTypes.
 */
class ChainedPluginProviderTypes extends BasePluginProviderType {

  /**
   * The plugin providers.
   *
   * @var \Drupal\plus\Plugin\PluginProviderTypeInterface[]
   */
  protected $providers;

  /**
   * PluginProviderTypes constructor.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface ...
   *   One or more plugin providers that implement
   *   \Drupal\plus\Plugin\PluginProviderTypeInterface.
   */
  public function __construct() {
    /** @var \Drupal\plus\Plugin\PluginProviderTypeInterface[] $providers */
    $providers = func_get_args();
    assert('Drupal\\Component\\Assertion\\Inspector::assertAllObjects($providers, \'\\Drupal\\plus\\Plugin\\PluginProviderTypeInterface\')', 'All passed arguments must be an instance of \Drupal\plus\Plugin\PluginProviderTypeInterface.');
    $this->providers = [];
    foreach ($providers as $provider) {
      $this->providers[$provider->getType()] = $provider;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, string ...$providers) {
    foreach ($providers as $key => $service_id) {
      $providers[$key] = $container->get($service_id);
    }
    return new static(...$providers);
  }

  /**
   * Invokes a method on all providers.
   *
   * @param string $method
   *   The method to invoke.
   * @param array $arguments
   *   An array of arguments to pass to the method.
   *
   * @return \Drupal\plus\Utility\ArrayObject
   *   An array of return values from each provider method. If the provider
   *   returns an array from its implementation, those are merged into the
   *   single results array recursively. Note: integer keys in arrays will be
   *   lost, as the merge is done using NestedArray::mergeDeep().
   */
  protected function invokeProviders($method, array $arguments = []) {
    $return = ArrayObject::create();
    foreach ($this->providers as $provider) {
      // Since profiles and modules both use the Module Handler service, skip
      // any profile alters so it doesn't duplicate when module runs.
      // @todo Remove in 8.6.x.
      // @see https://www.drupal.org/node/2709919
      if ($method === 'alterDefinitions' && $provider->getType() === 'profile' && isset($this->providers['module'])) {
        continue;
      }
      $result = call_user_func_array([$provider, $method], $arguments);
      if (isset($result)) {
        if ($result instanceof ArrayObject || is_array($result)) {
          $return->mergeDeep($result);
        }
        else {
          $return->append($result);
        }
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function alterDefinitions($hook, array &$definitions) {
    $this->invokeProviders(__FUNCTION__, [$hook, &$definitions]);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespaces($name = NULL, $type = NULL) {
    return $this->invokeProviders(__FUNCTION__, func_get_args());
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return implode(':', array_keys($this->providers));
  }

  /**
   * {@inheritdoc}
   */
  public function providerExists($provider) {
    $exists = $this->invokeProviders(__FUNCTION__, func_get_args());
    return !$exists->isEmpty();
  }

}
