<?php

namespace Drupal\webform_quiz\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Webform quiz submit handler item annotation object.
 *
 * @see \Drupal\webform_quiz\Plugin\WebformQuizSubmitHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebformQuizSubmitHandler extends Plugin {


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
