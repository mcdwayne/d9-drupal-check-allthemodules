<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\Plugin\Type\GridBuilderManager.
 */

namespace Drupal\gridbuilder\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Factory\ReflectionFactory;

/**
 * GridBuilder plugin manager.
 */
class GridBuilderManager extends PluginManagerBase {

  protected $defaults = array(
    'class' => 'Drupal\gridbuilder\Plugin\gridbuilder\gridbuilder\EqualColumnGrid',
  );

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::__construct().
   */
  public function __construct() {
    // Create gridbuilder plugin derivatives from declaratively defined grids.
    $this->discovery = new DerivativeDiscoveryDecorator(new AnnotatedClassDiscovery('gridbuilder', 'gridbuilder'));
    $this->factory = new ReflectionFactory($this);
  }
}
