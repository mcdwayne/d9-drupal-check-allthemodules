<?php

namespace Drupal\bibcite\Plugin;

use Drupal\bibcite\Plugin\Factory\FormatFactory;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Provides the default bibcite_format manager.
 */
class BibciteFormatManager extends DefaultPluginManager implements BibciteFormatManagerInterface {

  /**
   * Constructs a BibciteFormatManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'bibcite_format', ['bibcite_format']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('bibcite_format', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label', 'label_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFactory() {
    if (!$this->factory) {
      $this->factory = new FormatFactory($this, $this->pluginInterface);
    }
    return $this->factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    if (empty($definition['id'])) {
      throw new PluginException(sprintf('Example plugin property (%s) definition "is" is required.', $plugin_id));
    }
  }

  /**
   * Get definitions filtered by subclass.
   *
   * @param object|string $subclass
   *   A class name or an object instance.
   *
   * @return array|null
   *   List of filtered plugin definitions.
   */
  protected function filterDefinitionsBySubclass($subclass) {
    $definitions = $this->getDefinitions();

    return array_filter($definitions, function ($definition) use ($subclass) {
      return is_subclass_of($definition['encoder'], $subclass);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefinitions() {
    return $this->filterDefinitionsBySubclass(EncoderInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getImportDefinitions() {
    return $this->filterDefinitionsBySubclass(DecoderInterface::class);
  }

}
