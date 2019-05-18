<?php

namespace Drupal\assembly\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Defines an interface for Assembly build plugins.
 */
interface AssemblyBuildInterface extends PluginInspectionInterface {

  function build(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode);

}
