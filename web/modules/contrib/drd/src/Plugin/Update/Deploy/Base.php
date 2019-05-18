<?php

namespace Drupal\drd\Plugin\Update\Deploy;

use Drupal\drd\Plugin\Update\Base as UpdateBase;
use Drupal\drd\Update\PluginDeployInterface;
use Drupal\drd\Update\PluginStorageInterface;

/**
 * Abstract DRD Update plugin to implement general deploy functionality.
 */
abstract class Base extends UpdateBase implements PluginDeployInterface {

  protected $succeeded = FALSE;

  /**
   * {@inheritdoc}
   */
  public function dryRun(PluginStorageInterface $storage) {
    $storage->log('Nothing to do, dry run.');
    $this->succeeded = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  final public function hasSucceeded() {
    return $this->succeeded;
  }

}
