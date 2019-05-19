<?php

namespace Drupal\solr_qb\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class SolrQbDriver extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the driver plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Configuration variable name.
   *
   * @var string
   */
  public $configName;

}