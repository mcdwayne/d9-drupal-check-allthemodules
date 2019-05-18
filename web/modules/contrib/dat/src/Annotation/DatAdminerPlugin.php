<?php

namespace Drupal\dat\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DAT Adminer plugin item annotation object.
 *
 * @see \Drupal\dat\DatAdminerPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DatAdminerPlugin extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the archiver plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The weight of the plugin in its group.
   *
   * @var int
   */
  public $weight;

  /**
   * The weight of the plugin in its group.
   *
   * @var int
   */
  public $group;

  /**
   * The Adminer Type that can be used with this plugin.
   *
   * @var string[]
   */
  public $allowed_types = [];

}
