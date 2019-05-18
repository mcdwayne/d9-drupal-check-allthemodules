<?php

namespace Drupal\drd\Plugin\Update\Process;

use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a update process plugin that does nothing.
 *
 * @Update(
 *  id = "noprocess",
 *  admin_label = @Translation("No Process"),
 * )
 */
class None extends Base {

  /**
   * {@inheritdoc}
   */
  public function process(PluginStorageInterface $storage) {
    parent::process($storage);
    $this->succeeded = TRUE;
    return $this;
  }

}
