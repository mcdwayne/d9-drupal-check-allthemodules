<?php

/**
 * @file
 */

namespace Drupal\dhis\Services;

use \Drupal\dhis\Util\Validator;

class OrgUnitService implements OrgUnitServiceInterface
{
    private $loginService;
    private $orgUnitEndPoint = "organisationUnits";
    private $orgUnitLevelEndPoint = "organisationUnitLevels";
    private $orgUnitGroupEndPoint = "organisationUnitGroups";
    private $baseURL;

    public function __construct(DhisLogin $loginService)
    {
        $this->loginService = $loginService;
        // $this->baseURL = $baseURL;
    }

    public function getOrgUnitByCode($code, $format = "JSON")
    {
        //$format = $this->verifyFormat($format);
        $orgUnitEndPoint = $this->orgUnitEndPoint . "/" . $code . "." . Validator::verifyFormat($format) . "?fields=id,displayName";
        return $this->loginService->login($orgUnitEndPoint);
    }

    public function getOrgUnits($isPaginated = TRUE, $format = "JSON")
    {
        //$format = $this->verifyFormat($format);
        $orgUnitEndPoint = $this->orgUnitEndPoint . "." . Validator::verifyFormat($format) . "?fields=id,displayName&paging=" . Validator::verifyPagination($isPaginated);

        return $this->loginService->login($orgUnitEndPoint);
    }

    public function getOrgUnitsByLevel($level, $isPaginated = TRUE, $format = "JSON")
    {
        //$format = $this->verifyFormat($format);
        $orgUnitEndPoint = $this->orgUnitEndPoint . "." . Validator::verifyFormat($format) . "?filter=level:eq:" . $level . "&fields=id,displayName&paging=" . Validator::verifyPagination($isPaginated);
        return $this->loginService->login($orgUnitEndPoint);
    }

    public function getOrgUnitLevels($isPaginated = TRUE, $format = "JSON")
    {
        $orgUnitLevelEndPoint = $this->orgUnitLevelEndPoint . "." . Validator::verifyFormat($format) . "?fields=id,displayName,level&paging=" . Validator::verifyPagination($isPaginated);
        return $this->loginService->login($orgUnitLevelEndPoint);
    }

    public function getOrgUnitAncestry($code, $format = "JSON")
    {
        $orgUnitEndPoint = $this->orgUnitEndPoint . "/" . $code . "." . Validator::verifyFormat($format) . "?fields=id,displayName,children[id,displayName],ancestors[id,displayName]";
        return $this->loginService->login($orgUnitEndPoint);
    }

    public function getOrgUnitGroups($isPaginated = TRUE, $format = "JSON")
    {
        $orgUnitGroupEndPoint = $this->orgUnitGroupEndPoint . "." . Validator::verifyFormat($format) . "?fields=id,code,displayName&paging=" . Validator::verifyPagination($isPaginated);
        return $this->loginService->login($orgUnitGroupEndPoint);
    }

    public function getOrgUnitsByGroup($orgUnitGroupUid, $format = "JSON")
    {
        $orgUnitGroupEndPoint = $this->orgUnitGroupEndPoint . "/" . $orgUnitGroupUid . "." . Validator::verifyFormat($format) . "?fields=organisationUnits[id,displayName]";
        return $this->loginService->login($orgUnitGroupEndPoint);
    }
}