<?php

namespace Drupal\entity_router\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * The definer of the "EntityResponseHandler" plugins.
 *
 * @Target({"CLASS"})
 * @Annotation
 */
class EntityResponseHandler extends Plugin {

  public const DIRECTORY = 'Plugin/EntityResponseHandler';

  /**
   * The ID of a plugin and the name of a request format it handles.
   *
   * @var string
   * @Required
   */
  public $id;

  /**
   * The list of module names the handler depends on.
   *
   * @var string[]
   */
  public $dependencies;

}
