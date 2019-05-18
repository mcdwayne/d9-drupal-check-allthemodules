<?php

namespace Drupal\cleverreach\Controller\Ajax;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Build first email endpoint.
 */
class CleverreachFirstEmailController {
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function post(Request $request) {
    if (!$request->isMethod('POST')) {
      return new JsonResponse(['status' => 'failed']);
    }

    $this->getConfigService()->setIsFirstEmailBuilt(TRUE);
    return new JsonResponse(['status' => 'success']);
  }

  /**
   * Gets CleverReach configuration service.
   *
   * @return \Drupal\cleverreach\Component\Infrastructure\ConfigService
   */
  private function getConfigService() {
    if (NULL === $this->configService) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configService;
  }

}
