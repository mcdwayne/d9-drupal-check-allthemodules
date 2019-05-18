<?php

namespace Drupal\elastic_search\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class ElasticAbstractField.
 *
 * Plugin used to manage fields that are not entities but simple arrays/strings.
 *
 * @package Drupal\elastic_search\Annotation
 *
 * @Annotation
 * @codeCoverageIgnore
 */
class ElasticAbstractField extends Plugin {

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
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The field types supported by this plugin.
   *
   * @var array
   */
  public $field_types = [];

  /**
   * The plugin weight.
   *
   * @var integer
   */
  public $weight = 0;

}
