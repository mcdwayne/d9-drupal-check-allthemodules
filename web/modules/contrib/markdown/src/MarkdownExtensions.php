<?php

namespace Drupal\markdown;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\markdown\Annotation\MarkdownExtension;
use Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownParserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MarkdownExtensions.
 *
 * @method \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface createInstance($plugin_id, array $configuration = [])
 */
class MarkdownExtensions extends DefaultPluginManager implements MarkdownExtensionsInterface, FallbackPluginManagerInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Markdown/Extension', $namespaces, $module_handler, MarkdownExtensionInterface::class, MarkdownExtension::class);
    $this->setCacheBackend($cache_backend, 'markdown_extensions');
    $this->alterInfo('markdown_extensions');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    // Remove any plugins that don't actually have the parser installed.
    foreach ($definitions as $plugin_id => $definition) {
      if ($plugin_id === '_broken' || empty($definition['checkClass'])) {
        continue;
      }
      if (!class_exists($definition['checkClass'])) {
        unset($definitions[$plugin_id]);
      }
    }
    parent::alterDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('container.namespaces'),
      $container->get('cache.discovery'),
      $container->get('module_handler')
    );
    $instance->setContainer($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions($parser = NULL, $enabled = NULL) {
    // Normalize parser to a string representation of its plugin identifier.
    if ($parser instanceof MarkdownParserInterface) {
      $parser = $parser->getPluginId();
    }

    $extensions = [];
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      // Skip extensions that don't belong to a particular parser.
      if (isset($parser) && (!isset($definition['parser']) || $definition['parser'] !== $parser)) {
        continue;
      }
      try {
        $extension = $this->createInstance($plugin_id);
        if ($enabled === TRUE && $extension->isEnabled()) {
          $extensions[$plugin_id] = $extension;
        }
        elseif ($enabled === FALSE && !$extension->isEnabled()) {
          $extensions[$plugin_id] = $extension;
        }
        elseif ($enabled === NULL) {
          $extensions[$plugin_id] = $extension;
        }
      }
      catch (PluginException $e) {
        // Intentionally left empty.
      }
    }
    return $extensions;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return '_broken';
  }

}
