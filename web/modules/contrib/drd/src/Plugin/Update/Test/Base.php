<?php

namespace Drupal\drd\Plugin\Update\Test;

use Drupal\drd\Plugin\Update\Base as UpdateBase;
use Drupal\drd\Update\PluginTestInterface;

/**
 * Abstract DRD Update plugin to implement general test functionality.
 */
abstract class Base extends UpdateBase implements PluginTestInterface {

  protected $succeeded = FALSE;

  /**
   * {@inheritdoc}
   */
  final public function hasSucceeded() {
    return $this->succeeded;
  }

}
