<?php

namespace Drupal\social_share;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\typed_data\Context\AnnotatedClassDiscovery;
use Drupal\social_share\Annotation\SocialShareLink;

/**
 * Manager for social share link plugins.
 *
 * @see \Drupal\social_share\SocialShareLinkInterface
 */
class SocialShareLinkManager extends DefaultPluginManager implements SocialShareLinkManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * Constructs the object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('social_share_link');
    parent::__construct('Plugin/SocialShareLink', $namespaces, $module_handler, SocialShareLinkInterface::class, SocialShareLink::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!$this->discovery) {
      // Swap out the annotated class discovery used, so we can control the
      // annotation classes picked.
      $discovery = new AnnotatedClassDiscovery($this->subdir, $this->namespaces, $this->pluginDefinitionAnnotationName);
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Ensure the data type of the context matches and keep the context
   * separate if it does not.
   */
  public function getMergedContextDefinitions(array $plugin_ids) {

    // Collect all needed context definitions and remember which link needs
    // which context.
    $used_context = [];
    $used_by_plugins = [];
    $definitions = $this->getDefinitions();

    foreach ($plugin_ids as $plugin_id) {
      // Just silently ignore outdated, gone plugins.
      if (!isset($definitions[$plugin_id])) {
        continue;
      }
      foreach ($definitions[$plugin_id]['context'] as $name => $context_definition) {
        $used_context[$name] = $context_definition;
        $used_by_plugins += [$name => []];
        $used_by_plugins[$name][] = $plugin_id;
      }
    }
    return [$used_context, $used_by_plugins];
  }

}
