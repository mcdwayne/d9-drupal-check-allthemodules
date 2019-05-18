<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes Render plugin
 *
 * @Annotation
 *
 * @see \Drupal\hidden_tab\Plugable\Render\HiddenTabRenderInterface
 */
class HiddenTabRenderAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
