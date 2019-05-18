<?php

namespace Drupal\cloud\Controller;

use Drupal\cloud\Entity\CloudServerTemplateInterface;

/**
 * Common interfaces for the CloudServerTemplateControllerInterface.
 */
interface CloudServerTemplateControllerInterface {

  /**
   * Launch Operation.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function launch(CloudServerTemplateInterface $cloud_server_template);

}
