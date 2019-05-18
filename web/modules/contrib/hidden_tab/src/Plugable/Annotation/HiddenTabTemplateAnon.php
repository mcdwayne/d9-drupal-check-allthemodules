<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes a Template plugin.
 *
 * @Annotation
 *
 * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface
 */
class HiddenTabTemplateAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
