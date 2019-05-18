<?php
/**
 * @file
 * Contains \Drupal\mailjet_campaign\Controller\CampaignCallbackController.
 */

namespace Drupal\mailjet_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;

class CampaignCallbackController extends ControllerBase {

    public function callback() {
        _mailjet_campaign_alter_callback();
        die();
    }
}