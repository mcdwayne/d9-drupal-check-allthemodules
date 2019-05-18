<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes Tpl Context plugin.
 *
 * @Annotation
 *
 * @see \Drupal\hidden_tab\Plugable\TplContext\HiddenTabTplContextInterface
 */
class HiddenTabTplContextAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
