<?php

namespace Drupal\drd\Plugin\Update\Test;

use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a update test plugin that does nothing.
 *
 * @Update(
 *  id = "notest",
 *  admin_label = @Translation("No Test"),
 * )
 */
class None extends Base {

  /**
   * {@inheritdoc}
   */
  public function test(PluginStorageInterface $storage) {
    $this->succeeded = TRUE;
    return $this;
  }

}
