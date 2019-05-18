<?php

namespace Drupal\link_badges;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\views\Views;

class LinkBadgePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler, $annotation = 'Drupal\link_badges\Annotation\LinkBadgeBadge') {
    $subdir = 'Plugin/LinkBadge';
    $plugin_definition_annotation_name = 'Drupal\link_badges\Annotation\LinkBadge';
    $interface = 'Drupal\link_badges\LinkBadgeInterface';
    
    parent::__construct($subdir, $namespaces, $module_handler, $interface, $plugin_definition_annotation_name);
    $this->alterInfo('link_badge_info');
    $this->setCacheBackend($cache_backend, 'link_badge_info');
  }

}

