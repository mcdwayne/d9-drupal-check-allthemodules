<?php

namespace Drupal\presshub\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Presshub template annotation object.
 *
 * Plugin Namespace: Plugin\presshub\Presshub
 *
 * @see \Drupal\presshub\Plugin\PresshubManager
 * @see plugin_api
 *
 * @Annotation
 */
class Presshub extends Plugin {

  /**
   * The template ID.
   *
   * @var string
   */
  public $id;

  /**
   * Presshub template name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Supported entity types.
   *
   * @var array
   */
  public $entity_types = [];

}
