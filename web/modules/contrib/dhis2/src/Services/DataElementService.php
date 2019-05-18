<?php


namespace Drupal\dhis\Services;

use \Drupal\dhis\Util\Validator;
use \Drupal\dhis\Util\ArrayUtil;


class DataElementService implements DataElementServiceInterface
{
    private $loginService;
    private $dataElementEndPoint = "dataElements";
    private $datasetEndPoint = "dataSets";

    // private $baseURL;

    public function __construct($loginService)
    {
        $this->loginService = $loginService;
        //$this->baseURL = $baseURL;
    }

    public function getDataElementByCode($code, $format = "JSON")
    {
        $dataElementEndPoint = $this->dataElementEndPoint . "/" . $code . "." . Validator::verifyFormat($format) . "?fields=id,code,displayName";
        return $this->loginService->login($dataElementEndPoint);
    }

    public function getDataElements($isPaginated = TRUE, $format = "JSON")
    {
        $dataElementEndPoint = $this->dataElementEndPoint . "." . Validator::verifyFormat($format) . "?fields=id,code,displayName&paging=" . Validator::verifyPagination($isPaginated);
        return $this->loginService->login($dataElementEndPoint);
    }

    public function getDatasetDataElements($datasetCode, $isPaginated = TRUE, $format = "JSON")
    {
        //$dataElementEndPoint = $this->baseURL."23/".$this->dataElementEndPoint."/".$datasetCode.".".Validator::verifyFormat($format)."?fields=dataSetElements[id,displayName]&paging=".Validator::verifyPagination($isPaginated);
        $dataElementEndPoint = $this->datasetEndPoint . "/" . $datasetCode . "." . Validator::verifyFormat($format) . "?fields=dataSetElements[dataElement[id,code,name]]&paging=" . Validator::verifyPagination($isPaginated);
        return $this->loginService->login($dataElementEndPoint);
    }

    public function getDataElementValues($dataElementCodes = array(), $periods = array(), $orgUnits = array())
    {
        $analyticsConfig = '&tableLayout=true&columns=dx;ou&rows=pe&hideEmptyRows=true';
        $analytics = 'analytics.json?dimension=dx:' . ArrayUtil::implodeArray($dataElementCodes) . '&dimension=pe:' . ArrayUtil::implodeArray($periods) . '&dimension=ou:' . ArrayUtil::implodeArray($orgUnits) . $analyticsConfig;

        return $this->loginService->login($analytics);
    }
}