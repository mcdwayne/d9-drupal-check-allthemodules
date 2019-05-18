<?php

/**
 * @file
 */

namespace Drupal\dhis\Services;

interface OrgUnitServiceInterface
{
    public function getOrgUnitByCode($code, $format);

    public function getOrgUnits($isPaginated = TRUE, $format = "JSON");

    public function getOrgUnitsByLevel($level, $isPaginated, $format);

    public function getOrgUnitLevels($isPaginated, $format);

    public function getOrgUnitAncestry($code, $format);

    public function getOrgUnitGroups($isPaginated, $format);

    public function getOrgUnitsByGroup($orgUnitGroupUid, $format);
}