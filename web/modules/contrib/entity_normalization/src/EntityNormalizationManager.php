<?php

namespace Drupal\entity_normalization;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Provides an entity normalization manager using YML as primary definition.
 */
class EntityNormalizationManager extends DefaultPluginManager implements EntityNormalizationManagerInterface {

  private static $cache = [];

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'format' => NULL,
    'weight' => 0,
    'class' => EntityConfig::class,
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    // Skip calling the parent constructor, since that assumes
    // annotation-based discovery.
    $this->pluginInterface = EntityConfigInterface::class;
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'entity_normalization', ['entity_normalization']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      $yaml_discovery = new YamlDiscovery('entity_normalization', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = $this->getDiscovery()->getDefinitions();

    $this->expandExtendedDefinitions($definitions);

    foreach ($definitions as $plugin_id => &$definition) {
      $this->processDefinition($definition, $plugin_id);
    }
    $this->alterDefinitions($definitions);

    uasort($definitions, function (array $first, array $second) {
      // Sort by format, then by weight.
      if ($second['format'] === $first['format']) {
        if ($second['weight'] !== $first['weight']) {
          return $second['weight'] > $first['weight'] ? 1 : -1;
        }
      }
      return $first['format'] ? -1 : 1;
    });
    return $definitions;
  }

  /**
   * Expand the definitions which extend another definition.
   *
   * @param array $definitions
   *   A list of definitions.
   */
  protected function expandExtendedDefinitions(array &$definitions) {
    while ($this->needToExtendDefinitions($definitions)) {
      foreach ($definitions as &$definition) {
        if (!isset($definition['extends'])) {
          continue;
        }
        // @todo Fix if not exist.
        $baseDefinition = $definitions[$definition['extends']];
        if (isset($baseDefinition['extends'])) {
          // If the baseDefinition also extends something,
          // do the current one later.
          continue;
        }

        $definition += ['weight' => 0];
        $definition = array_merge($baseDefinition, $definition);
        if (isset($baseDefinition['fields'])) {
          // Using array_merge so the fields from the base definition are
          // before the fields from the extended definition.
          $definition['fields'] = array_merge($baseDefinition['fields'], $definition['fields']);
        }
        unset($definition['extends']);
      }
    }
  }

  /**
   * Do we still need to extend the definitions?
   *
   * @param array $definitions
   *   The list of definitions.
   *
   * @return bool
   *   Indicates if there are definitions to extend.
   */
  protected function needToExtendDefinitions(array $definitions) {
    foreach ($definitions as $definition) {
      if (isset($definition['extends'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (empty($definition['type'])) {
      throw new PluginException(sprintf('Entity normalization (%s) definition must include "type".', $plugin_id));
    }
    if (empty($definition['bundle'])) {
      throw new PluginException(sprintf('Entity normalization (%s) definition must include "bundle".', $plugin_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityConfig(FieldableEntityInterface $entity, $format = NULL) {
    return $this->getEntityConfig($entity, $format) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityConfig(FieldableEntityInterface $entity, $format = NULL) {
    $cacheKey = $entity->getEntityTypeId() . '|' . $entity->bundle() . '|' . ($format !== NULL ? $format : '');
    if (isset(self::$cache[$cacheKey])) {
      return self::$cache[$cacheKey];
    }
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      if ($definition['type'] === $entity->getEntityTypeId() &&
        in_array($entity->bundle(), is_array($definition['bundle']) ? $definition['bundle'] : [$definition['bundle']]) &&
        ($definition['format'] === NULL || $definition['format'] === $format)
      ) {
        $instance = $this->createInstance($id);
        self::$cache[$cacheKey] = $instance;
        return $instance;
      }
    }
    self::$cache[$cacheKey] = NULL;
    return NULL;
  }

}
