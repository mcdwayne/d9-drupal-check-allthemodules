<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes a Access plugin.
 *
 * @Annotation
 *
 * @see \Drupal\hidden_tab\Plugable\Access\HiddenTabAccessInterface
 */
class HiddenTabAccessAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
