<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\Infrastructure\Logger\Logger;

/**
 * Class ProductSearchSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class ProductSearchSyncTask extends BaseSyncTask
{
    /**
     * Runs task execution.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $productSearchParameters = $this->getConfigService()->getProductSearchParameters();

        $this->validateProductSearchParameters($productSearchParameters);

        $id = $this->getProxy()->addOrUpdateProductSearch($productSearchParameters);
        $this->getConfigService()->setProductSearchContentId($id);

        $this->reportProgress(100);
    }

    /**
     * Validate if all product search parameters are set.
     *
     * @param array|null $productSearchParameters Associative array of product search parameters.
     *     Expected keys name, url and password.
     */
    private function validateProductSearchParameters($productSearchParameters)
    {
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
