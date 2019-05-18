<?php
/**
 * @file
 * Contains
 *   \Drupal\mailjet_trigger_examples\Controller\TriggerExamplesController.
 */

namespace Drupal\mailjet_trigger_examples\Controller;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\user\Entity\User;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;


use Drupal\Core\Controller\ControllerBase;

class TriggerExamplesController extends ControllerBase {

  public function content() {

    global $base_url;
    return mailjet_go_to_external_link($base_url . '/admin/structure/message');

  }
}

