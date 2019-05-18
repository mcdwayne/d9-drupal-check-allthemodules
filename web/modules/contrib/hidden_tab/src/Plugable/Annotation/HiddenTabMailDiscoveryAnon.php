<?php

namespace Drupal\hidden_tab\Plugable\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Denotes Mail Discovery plugin.
 *
 * @Annotation
 *
 * @see \Drupal\hidden_tab\Plugable\MailDiscovery\HiddenTabMailDiscoveryInterface
 */
class HiddenTabMailDiscoveryAnon extends Plugin {

  /**
   * Id of the plugin.
   *
   * @var string
   */
  public $id;

}
