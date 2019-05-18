<?php
/**
 * @file
 * Contains \Drupal\mailjet_campaign\Controller\CampaignUninstallController.
 */

namespace Drupal\mailjet_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;

class CampaignUninstallController extends ControllerBase {

  public function callback() {
    $build = [];

    $controller = \Drupal::entityTypeManager()->getStorage('campaign_entity');
    $entities = $controller->loadMultiple();
    $controller->delete($entities);
    
    drupal_set_message(t('Campaign entities is removing succcefully!'));
    return $build;

  }
}