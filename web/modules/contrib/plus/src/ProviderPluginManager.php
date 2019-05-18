<?php

namespace Drupal\plus;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\plus\Plugin\Discovery\AccumulativeAnnotatedClassDiscovery;
use Drupal\plus\Plugin\PluginProviderTypeInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Base class for Bootstrap plugin managers.
 *
 * @ingroup utility
 *
 * @property \Drupal\plus\Utility\ArrayObject namespaces
 */
abstract class ProviderPluginManager extends DefaultPluginManager implements ContainerAwareInterface, ContainerInjectionInterface {

  use ContainerAwareTrait;

  /**
   * The plugin provider type.
   *
   * @var \Drupal\plus\Plugin\PluginProviderTypeInterface
   */
  protected $providerType;

  /**
   * Flag indicating whether or not discovery should accumulate or replace.
   *
   * Enabling this means simply means that all plugin identifiers will be
   * prefixed with their provider name separated by a colon, e.g.:
   * "provider_name:plugin_id" instead of just "plugin_id".
   *
   * The primary purpose behind this level of granularity is to ensure that if
   * two providers supply the same plugin identifier (often to correlate with
   * another plugin identifier or hook), the current definition doesn't replace
   * previous definition.
   *
   * This allows managers to effectively load plugins from all providers and
   * then filter out which ones they need based on various getter methods below.
   *
   * @var bool
   */
  protected $accumulativeDiscovery = FALSE;

  /** @noinspection PhpMissingParentConstructorInspection */

  /**
   * Creates the discovery object.
   *
   * @param \Drupal\plus\Plugin\PluginProviderTypeInterface $provider_type
   *   The plugin provider type used for discovery.
   * @param string|bool $subdir
   *   The plugin's subdirectory, for example Plugin/views/filter.
   * @param string|null $plugin_interface
   *   (optional) The interface each plugin should implement.
   * @param string $plugin_definition_annotation_name
   *   (optional) The name of the annotation that contains the plugin
   *   definition. Defaults to 'Drupal\Component\Annotation\Plugin'.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   (optional) The backend cache service to use.
   * @param string[] $additional_annotation_namespaces
   *   (optional) Additional namespaces to scan for annotation definitions.
   */
  public function __construct(PluginProviderTypeInterface $provider_type, $subdir, $plugin_interface = NULL, $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin', CacheBackendInterface $cache_backend = NULL, array $additional_annotation_namespaces = []) {
    // Set the provider type.
    $this->providerType = $provider_type;

    // Set properties used for annotated discovery.
    $this->subdir = $subdir;
    $this->namespaces = $this->providerType->getNamespaces();
    $this->pluginDefinitionAnnotationName = $plugin_definition_annotation_name;
    $this->pluginInterface = $plugin_interface;
    $this->additionalAnnotationNamespaces = $additional_annotation_namespaces;

    // Set cache backend.
    if ($cache_backend) {
      $this->setCacheBackend($cache_backend, $this->generateCacheKey(), $this->getCacheTags());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    if ($this->alterHook) {
      $this->providerType->alterDefinitions($this->alterHook, $definitions);
    }
  }

  /**
   * Retrieves all plugin instances.
   *
   * @param string[] $plugin_ids
   *   An array of plugin identifiers to create. If empty, all defined plugin
   *   instances will be returned.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object[]
   *   An associative array of plugin instances, keyed by plugin identifier.
   */
  public function createInstances(array $plugin_ids = [], array $configuration = []) {
    $instances = [];
    $definitions = $this->getDefinitions();
    if ($plugin_ids) {
      $definitions = array_intersect_key($definitions, array_flip($plugin_ids));
    }
    foreach ($definitions as $plugin_id => $definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id, $configuration);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->providerType->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->providerType->getCacheMaxAge();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->providerType->getCacheTags();
  }

  /**
   * Retrieves the cache identifier used for discovery.
   *
   * @return string
   *   The cache identifier.
   */
  protected function generateCacheKey() {
    $cid = [];
    $cid[] = 'plugin.manager.' . $this->providerType->getType();
    $cid[] = Html::getId((new \ReflectionClass($this))->getShortName());
    $cid[] = Html::getId($this->subdir);
    return implode(':', $cid);
  }

  /**
   * {@inheritdoc}
   *
   * @param bool $sorted
   *   Flag indicating whether to sort definitions by weight.
   */
  public function getDefinitions($sorted = TRUE) {
    $definitions = parent::getDefinitions();
    if ($sorted) {
      uasort($definitions, ['\Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    }
    return $definitions;
  }

  /**
   * Retrieves all definitions where the plugin ID matches a certain criteria.
   *
   * @param string $regex
   *   The regex pattern to match.
   * @param bool $sorted
   *   Flag indicating whether to sort definitions by weight.
   *
   * @return array[]
   *   An array of plugin definitions (empty array if no definitions were
   *   found). Keys are plugin IDs.
   */
  public function getDefinitionsLike($regex, $sorted = TRUE) {
    $definitions = [];
    foreach ($this->getDefinitions($sorted) as $plugin_id => $definition) {
      if (preg_match($regex, $plugin_id)) {
        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      if ($this->accumulativeDiscovery) {
        $discovery = new AccumulativeAnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      }
      else {
        $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName, $this->additionalAnnotationNamespaces);
      }
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function providerExists($provider) {
    return $this->providerType->providerExists($provider);
  }

}
