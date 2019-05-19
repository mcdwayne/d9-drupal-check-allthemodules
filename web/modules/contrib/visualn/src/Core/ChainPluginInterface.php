<?php

namespace Drupal\visualn\Core;

use Drupal\visualn\ResourceInterface;

/**
 * Defines an interface for VisualN Chain plugins.
 *
 * Chain plugin is a general purpose plugin that can be used for diverse use cases and workflows.
 * It doesn't depend on VisualN workflows and functionality.
 * The specifics of every chain plugin is defined by the use case and workflow it is used in. There
 * are no suggestions about its nature, role and the way it is used.
 * Chain plugins in some sense can be thought as UNIX pipes analogy that allows to create a chain
 * of multiple processes (programs) when output of the previous one is used as input for the next one.
 * Chain plugins use a Resource object that implement ResourceInterface that is passed along the chain of plugins.
 * Also there are no suggestions on how plugins use Resources and whether and how they change Resources.
 * The same can be said about $build element - what to do with $build, what it represents and how to treat
 * is fully defined by the workflow. As an example, $build can containt '#build_info' key that could be used
 * by chain plugins to communicate between each other (if Resource is not enough).
 * VisualN uses $build as a place to compose drawing render array.
 */
interface ChainPluginInterface {

  /**
   * Prepare build array.
   *
   * @param array $build
   *
   * @param string $vuid
   *
   * @param \Drupal\visualn\Core\VisualNResourceInterface $resource
   *
   * @return \Drupal\visualn\Core\VisualNResourceInterface $resource
   */
  public function prepareBuild(array &$build, $vuid, ResourceInterface $resource);

}
