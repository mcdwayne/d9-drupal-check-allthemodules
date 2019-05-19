<?php

namespace Drupal\system_tags\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class SystemTagFinder.
 *
 * @package Drupal\system_tags\Annotation
 *
 * @Annotation
 */
class SystemTagFinder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity type this plugin should look for.
   *
   * @var string
   */
  public $entity_type;

}
