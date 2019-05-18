<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes a Komponent plugin.
 *
 * @Annotation
 *j
 * @see \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentInterface
 */
class HiddenTabKomponentAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
