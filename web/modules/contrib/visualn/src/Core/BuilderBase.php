<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\ChainPluginJsTrait;
use Drupal\visualn\Core\BuilderInterface;
use Drupal\visualn\Core\VisualNPluginBase;
use Drupal\visualn\WindowParametersTrait;

/**
 * Base class for VisualN Builder plugins.
 *
 * @see \Drupal\visualn\Core\BuilderInterface
 *
 * @ingroup builder_plugins
 */
abstract class BuilderBase extends VisualNPluginBase implements BuilderInterface {

  // @todo: actually this should be moved to BuilderWithJsBase (see DrawerWithJsBase for example)
  //   and used as base class for DefaultBuilder plugin
  use ChainPluginJsTrait;
  use WindowParametersTrait;

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
      'html_selector' => '',
      // @todo: this was introduced later, for drawer preview page
      'base_drawer_id' => '',
    ];
  }

}
