<?php

namespace Drupal\views_oai_pmh\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a OAI-PMH Metadata Prefix item annotation object.
 *
 * @see \Drupal\views_oai_pmh\Plugin\MetadataPrefixManager
 * @see plugin_api
 *
 * @Annotation
 */
class MetadataPrefix extends Plugin {


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

  public $prefix;

}
