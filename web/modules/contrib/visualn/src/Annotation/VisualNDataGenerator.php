<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Data Generator item annotation object.
 *
 * @see \Drupal\visualn\Manager\DataGeneratorManager
 * @see plugin_api
 *
 * @Annotation
 */
class VisualNDataGenerator extends Plugin {


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
   * The raw resource format of the generator.
   *
   * @var string
   */
  // @todo: implement the raw resource format plugin and the resource provider logic
  //   to support formats for data generators
  //   it will allow for data generators to generate not only plain arrays but
  //   also, for example nested array which may be required by some drawers
  //   or just easier to generate
  // @todo: move into constant (?)
  public $raw_resource_format = 'visualn_generic_data_array';

  /**
   * The list of compatible base drawers ids.
   *
   * @var array
   */
  public $compatible_drawers = [];

}
