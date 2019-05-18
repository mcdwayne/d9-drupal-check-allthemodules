<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\Infrastructure\Logger\Logger;

/**
 *
 */
class ProductSearchSyncTask extends BaseSyncTask {

  /**
   * Runs task logic.
   *
   * @throws HttpCommunicationException
   * @throws HttpRequestException
   * @throws HttpAuthenticationException
   */
  public function execute() {
    $productSearchParameters = $this->getConfigService()->getProductSearchParameters();

    $this->validateProductSearchParameters($productSearchParameters);

    $this->getProxy()->addOrUpdateProductSearch($productSearchParameters);
    $this->reportProgress(100);
  }

  /**
   * Validate if all product search parameters are set.
   *
   * @param array $productSearchParameters
   */
  private function validateProductSearchParameters($productSearchParameters) {
    $errorMessage = '';

    if (empty($productSearchParameters['name'])) {
      $errorMessage .= 'Parameter "name" for product search is not set in Configuration service.';
    }

    if (empty($productSearchParameters['url'])) {
      $errorMessage .= 'Parameter "url" for product search is not set in Configuration service.';
    }

    if (empty($productSearchParameters['password'])) {
      $errorMessage .= 'Parameter "password" for product search is not set in Configuration service.';
    }

    if (!empty($errorMessage)) {
      Logger::logError($errorMessage);
      throw new \InvalidArgumentException($errorMessage);
    }
  }

}
