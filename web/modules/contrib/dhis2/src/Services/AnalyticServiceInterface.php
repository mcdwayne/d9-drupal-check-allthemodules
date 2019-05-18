<?php

namespace Drupal\dhis\Services;


interface AnalyticServiceInterface
{
    public function generateAnalytics(array $dataElements, array $orgUnits, array $periods);
}