<?php

namespace Drupal\imce_copylink\Plugin\ImcePlugin;

use Drupal\imce\Imce;
use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

/**
 * Defines Imce Copy Link plugin.
 *
 * @ImcePlugin(
 *   id = "copylink",
 *   label = "Copy Link",
 * )
 */

class CopyLink extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function permissionInfo() {
    return array('copylink' => $this->t('Copy link'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    if ($fm->hasPermission('copylink')) {
      $page['#attached']['library'][] = 'imce_copylink/drupal.imce.copylink';
    }
  }
}
