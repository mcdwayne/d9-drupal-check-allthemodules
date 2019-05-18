<?php

namespace Drupal\chatbot_api_entities\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Query handler item annotation object.
 *
 * @see \Drupal\chatbot_api_entities\Plugin\QueryHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class QueryHandler extends Plugin {


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
