<?php

namespace Drupal\system_tags\SystemTagFinder;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system_tags\Annotation\SystemTagFinder;

/**
 * Class SystemTagFinderManager.
 *
 * @package Drupal\system_tags\SystemTagFinder
 */
class SystemTagFinderManager extends DefaultPluginManager implements SystemTagFinderManagerInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SystemTagFinder', $namespaces, $module_handler, SystemTagFinderInterface::class, SystemTagFinder::class);

    $this->alterInfo('system_tag_finder_info');
    $this->setCacheBackend($cache_backend, 'system_tag_finders');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $entityTypeId = $options['entity_type'];

    $instance = NULL;
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      if ($definition['entity_type'] === $entityTypeId) {
        $instance = $this->createInstance($pluginId);
      }
    }

    if (!$instance) {
      throw new PluginException($this->t("No System Tag Finder found for entity type '@entity_type'", [
        '@entity_type' => $entityTypeId,
      ]));
    }

    return $instance;
  }

}
