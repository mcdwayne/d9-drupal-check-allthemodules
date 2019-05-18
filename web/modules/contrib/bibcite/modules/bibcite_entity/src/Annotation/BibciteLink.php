<?php

namespace Drupal\bibcite_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Link item annotation object.
 *
 * @see \Drupal\bibcite_entity\Plugin\BibciteLinkPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class BibciteLink extends Plugin {

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

}
