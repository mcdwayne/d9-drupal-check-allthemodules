<?php

namespace Drupal\drd\Plugin\Update\Storage;

use Drupal\drd\Plugin\Update\RsyncTrait;

/**
 * Provides a Rsync storage update plugin.
 *
 * @Update(
 *  id = "rsync",
 *  admin_label = @Translation("RSync from Live Site"),
 * )
 */
class Rsync extends Base {

  use RsyncTrait;

  /**
   * {@inheritdoc}
   */
  public function prepareWorkingDirectory() {
    parent::prepareWorkingDirectory();

    $this->sync($this, TRUE);
    return $this;
  }

}
