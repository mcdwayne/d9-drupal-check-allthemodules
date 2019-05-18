<?php

namespace Drupal\hn\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for HN Path resolver plugins.
 */
interface HnPathResolverInterface extends PluginInspectionInterface {

  /**
   * This method gets an entity by providing a path.
   *
   * @param string $path
   *   The path that needs to be changed to an entity.
   *
   * @return \Drupal\hn\HnPathResolverResponse|null
   *   Should return the resolved entity, or NULL if not found.
   */
  public function resolve($path);

}
