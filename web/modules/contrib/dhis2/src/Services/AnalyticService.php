<?php

namespace Drupal\dhis\Services;


class AnalyticService implements AnalyticServiceInterface
{

    private $loginService;
    private $analyticsEndpoint = 'analytics.json';

    public function __construct(DhisLogin $loginService)
    {
        $this->loginService = $loginService;
    }

    public function generateAnalytics(array $dataElements, array $orgUnits, array $periods)
    {
        $dx = implode(';', $dataElements);
        $ou = implode(';', $orgUnits);
        $pe = implode(';', $periods);
        $analyticsEndpoint = $this->analyticsEndpoint . '?dimension=dx:' . $dx . '&dimension=ou:' . $ou . '&dimension=pe:' . $pe . '&tableLayout=true&rows=dx;ou&columns=pe';
        return $this->loginService->login($analyticsEndpoint);
    }
}