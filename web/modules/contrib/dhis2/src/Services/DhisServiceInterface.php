<?php

namespace Drupal\dhis\Services;


interface DhisServiceInterface
{
    public function getDimensions($entity_type);

    public function removeDhisEntities($entity_type);

    public function createContent($dhis_data);

    public function analyticData($analyticsData);
    public function checkDhisEntities($entityType);
    public function createDhisEntities($metadata, $entity_type, $list = []);
}