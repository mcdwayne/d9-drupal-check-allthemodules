<?php

/**
 * @file
 * Definition of Drupal\entityreference\Plugin\Type\Selection\SelectionPluginManager.
 */

namespace Drupal\entityreference\Plugin\Type\Selection;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Plugin type manager for field widgets.
 */
class SelectionPluginManager extends PluginManagerBase {

  /**
   * The cache id used for plugin definitions.
   *
   * @var string
   */
  protected $cache_key = 'entityreference_selection';

  /**
   * Constructs a WidgetPluginManager object.
   */
  public function __construct() {
    $this->baseDiscovery = new AnnotatedClassDiscovery('entityreference', 'selection');
    $this->discovery = new CacheDecorator($this->baseDiscovery, $this->cache_key);
  }
}
