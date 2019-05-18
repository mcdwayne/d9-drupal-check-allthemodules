<?php

namespace Drupal\integro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\integro\Entity\ConnectorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes connector.
 */
class AuthController extends ControllerBase {

  /**
   * Authorizes connector.
   * @param \Drupal\integro\Entity\ConnectorInterface $integro_connector
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function auth(ConnectorInterface $integro_connector) {
    $integro_connector->auth();
    return $this->redirect('entity.integro_connector.collection');
  }

}
