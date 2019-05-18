<?php

namespace CleverReach\BusinessLogic\Sync;

class ExchangeAccessTokenTask extends BaseSyncTask
{
    /**
     * Refreshes CleverReach tokens.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function execute()
    {
        $this->reportProgress(5);

        $configService = $this->getConfigService();
        $configService->setAccessTokenExpirationTime(10000);

        $result = $this->getProxy()->exchangeToken();

        $configService->setAuthInfo($result);

        $this->reportProgress(100);
    }
}