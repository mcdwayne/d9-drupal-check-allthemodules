<?php

namespace Drupal\drd\Plugin\Update\Deploy;

use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a update deploy plugin that does nothing.
 *
 * @Update(
 *  id = "nodeploy",
 *  admin_label = @Translation("No Deployment"),
 * )
 */
class None extends Base {

  /**
   * {@inheritdoc}
   */
  public function deploy(PluginStorageInterface $storage) {
    $this->succeeded = TRUE;
    return $this;
  }

}
