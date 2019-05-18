<?php

namespace Drupal\drd\Plugin\Update\Deploy;

use Drupal\drd\Update\PluginStorageInterface;
use Drupal\drd\Plugin\Update\RsyncTrait;

/**
 * Provides a Rsync deploy update plugin.
 *
 * @Update(
 *  id = "rsync",
 *  admin_label = @Translation("RSync to Live Site"),
 * )
 */
class Rsync extends Base {

  use RsyncTrait;

  /**
   * {@inheritdoc}
   */
  public function deploy(PluginStorageInterface $storage) {
    $this->sync($storage, FALSE);
    $this->succeeded = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function dryRun(PluginStorageInterface $storage) {
    $this->sync($storage, FALSE, TRUE);
    $this->succeeded = TRUE;
    return $this;
  }

}
