<?php

namespace Drupal\site_alert\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\site_alert\Entity\SiteAlert;
use Symfony\Component\HttpFoundation\Response;

class SiteAlertController extends ControllerBase {

  public function getUpdatedAlerts() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $build = [];

    foreach (SiteAlert::loadMultiple() as $alert) {
      if ($alert->getActive() && $alert->isCurrentlyScheduled()) {
        $build[] = [
          '#theme' => 'site_alert',
          '#alert' => [
            'severity' => $alert->getSeverity(),
            'message' => [
              '#type' => 'markup',
              '#markup' => $alert->getMessage(),
            ],
          ],
          '#attached' => [
            'library' => ['site_alert/drupal.site_alert'],
          ],
        ];
      }
    }

    $html = \Drupal::service('renderer')->renderRoot($build);
    $response = new Response();
    $response->setContent($html);

    return $response;
  }

}
