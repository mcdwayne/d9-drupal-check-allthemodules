<?php

namespace Drupal\integro;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\integro\Annotation\IntegroIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manages discovery and instantiation of plugins.
 */
class IntegrationManager extends DefaultPluginManager implements IntegrationManagerInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The known integrations.
   *
   * @var \Drupal\integro\IntegrationInterface[]|null
   *   An array of integrations or NULL if integration discovery has not been
   *   executed yet.
   */
  protected $integrations;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(ContainerInterface $container, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Integro/Integration', $namespaces, $module_handler, IntegrationInterface::class, IntegroIntegration::class);
    $this->alterInfo('integro_integration');
    $this->setCacheBackend($cache_backend, 'integro_integration', ['integro_integration']);
    $this->container = $container;
    $this->moduleHandler = $module_handler;
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function hasIntegration($id) {
    return isset($this->getIntegrations()[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIntegration($id) {
    $integrations = $this->getIntegrations();
    if (isset($integrations[$id])) {
      return $integrations[$id];
    }
    else {
      throw new \InvalidArgumentException(sprintf('Integration "%s" is unknown.', $id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIntegrations() {
    // Return immediately if all data is available in the static cache.
    if (is_array($this->integrations)) {
      return $this->integrations;
    }
    else {
      return $this->discoverIntegrations();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return array_map(function (IntegrationInterface $integration) {
      return $integration->getDefinition()->getLabel();
    }, $this->getIntegrations());
  }

  /**
   * Discovers and returns an array of integrations.
   *
   * @return string[]
   */
  protected function discoverIntegrations() {
    $this->integrations = [];

    // Try restore integrations from cache.
    if ($cached = \Drupal::cache()->get('integro.integration')) {
      $this->integrations = $cached->data;
      return $this->integrations;
    }

    // \Drupal\Component\Discovery\YamlDiscovery::findAll() caches raw file
    // contents, but we want to cache integrations for better performance.
    $files = $this->findFiles();
    $providers_by_file = array_flip($files);
    $file_cache = FileCacheFactory::get('integro:integration');

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple($files) as $file => $integrations_by_file) {
      $this->integrations = array_merge($this->integrations, $integrations_by_file);
      unset($providers_by_file[$file]);
    }

    // List the available integration providers.
    $providers = array_map(function (Extension $module) {
      return $module->getName();
    }, $this->moduleHandler->getModuleList());
    // @todo Have a hope one day this module will go to core.
    $providers[] = 'core';

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    foreach ($providers_by_file as $file => $provider) {
      // If a file is empty or its contents are commented out, return an empty
      // array instead of NULL for type consistency.
      $integration_definitions = Yaml::decode(file_get_contents($file)) ?: [];

      // Set the integration definitions' default values.
      $integration_definition_defaults = [
        'provider' => $provider,
      ];
      $integration_definitions = array_map(function ($integration_id, array $integration_definition) use ($integration_definition_defaults) {
        $integration_definition['id'] = $integration_id;
        return $integration_definition + $integration_definition_defaults;
      }, array_keys($integration_definitions), $integration_definitions);

      // Remove definitions from uninstalled providers.
      $integration_definitions = array_filter($integration_definitions, function (array $integration_definition) use ($providers) {
        return in_array($integration_definition['provider'], $providers);
      });

      // Create integrations from their definitions.
      $file_integrations = [];
      foreach ($integration_definitions as $integration_definition) {
        if ($this->isValidIntegrationDefinition($integration_definition)) {

          $definition_plugin = $integration_definition['definition'];
          /** @var \Drupal\integro\DefinitionInterface $definition */
          $definition = $this->container->get('integro_definition.manager')->createInstance($definition_plugin, $integration_definition);

          // Discover operations.
//          $integration_definition['client_configuration'] = [
//            'protocol' => 'https',
//            'domain' => 'www.googleapis.com',
//            'base_uri' => '/',
//            'class' => '/Google_Client',
//          ];
//          $integration_definition['operations'] = [
//            'objects.listObjects' => [
//              'type' => 'native',
//              'arguments' => [
//                'bucket' => 'string',
//                'optParams' => 'array',
//              ],
//            ],
//            'objects.insert' => [
//
//            ],
//            'objects.get' => [
//              'type' => 'native',
//              'arguments' => [
//                'bucket' => 'string',
//                'object' => 'string',
//                'optParams' => 'array',
//              ],
//            ],
//            'objects.delete' => [
//
//            ],
//
//          ];
//          $integration_definition_yaml = Yaml::encode($integration_definition);

          $operations = $this->discoverOperations($integration_definition);
          $definition->setOperations($operations);

          $integration_plugin = $integration_definition['integration'];
          /** @var \Drupal\integro\IntegrationInterface $integration */
          $integration = $this->createInstance($integration_plugin, []);
          $integration->setDefinition($definition);

          $file_integrations[$integration->getDefinition()->getId()] = $integration;

//          $file_integrations[$integration_definition['id']] = $integration_definition;
        }
      }

      // Store the integrations in the static and file caches.
      $this->integrations += $file_integrations;
      $file_cache->set($file, $file_integrations);

      // Store the integrations in the regular cache.
      \Drupal::cache()->set('integro.integration', $this->integrations);
    }

    return $this->integrations;
  }

  /**
   * Discovers and returns an array of operations.
   *
   * @param $integration_definition
   * @return \string[]
   */
  protected function discoverOperations($integration_definition) {
    $operations = [];

    if (isset($integration_definition['operations']) && $integration_definition['operations']) {
      foreach ($integration_definition['operations'] as $operation_id => $operation_definition) {
        // @todo Add operation validation.
        $operations[$operation_id] = $operation_definition;
      }
    }

    return $operations;
  }

  /**
   * Validates integration definition.
   *
   * @param array $integration_definition
   * @return bool
   */
  protected function isValidIntegrationDefinition(array $integration_definition) {
    // Create a definition that specifies some constraint.
    $integration_definition_typed = MapDataDefinition::create()
      ->setPropertyDefinition('definition', DataDefinition::create('string'))
      ->addConstraint('IntegroDefinition');

    // Validate.
    $integration_definition_typed_data = $this->typedDataManager->create($integration_definition_typed, ['definition' => $integration_definition['definition']]);
    $violations = $integration_definition_typed_data->validate();

    if ($violations->count() > 0) {
      foreach ($violations as $violation) {
        drupal_set_message($violation->getMessage(), 'warning');
      }
    }

    return $violations->count() == 0;
  }

  /**
   * Returns an array of file paths, keyed by provider.
   *
   * @return string[]
   */
  protected function findFiles() {
    $files = [];
    foreach ($this->moduleHandler->getModuleDirectories() as $provider => $directory) {
      $file = $directory . '/' . $provider . '.integro.yml';
      if (file_exists($file)) {
        $files[$provider] = $file;
      }
    }
    return $files;
  }

}
