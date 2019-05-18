<?php

namespace Drupal\freshmail\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class use to connection with freshmail API.
 * Based on class get form https://github.com/FreshMail/REST-API .
 *
 * Class FreshmailRestController
 *
 * @package Drupal\freshmail\Controller
 */
class FreshmailRestController extends ControllerBase {

  const HOST = 'https://api.freshmail.com/';
  const PREFIX = 'rest/';
  const DEFAULTFILEPATH = '/tmp/';
  private $response = NULL;
  private $rawResponse = NULL;
  private $httpCode = NULL;
  private $contentType = 'application/json';

  protected $config;

  /**
   * FreshmailRestController constructor.
   */
  public function __construct() {
    $this->config = $this->config('freshmail.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    if (isset($this->errors['errors'])) {
      return $this->errors['errors'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawResponse() {
    return $this->rawResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpCode() {
    return $this->httpCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentType($contentType = '') {
    $this->contentType = $contentType;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function doRequest($strUrl, $arrParams = array(), $boolRawResponse = FALSE) {
    if (empty($arrParams)) {
      $strPostData = '';
    }
    elseif ($this->contentType == 'application/json') {
      $strPostData = json_encode($arrParams);
    }
    elseif (!empty($arrParams)) {
      $strPostData = http_build_query($arrParams);
    }

    $api_key = $this->config->get('freshmail_api_key');
    $api_secret = $this->config->get('freshmail_api_secret_key');
    $strSign = sha1($api_key . '/' . self::PREFIX . $strUrl . $strPostData . $api_secret);
    $arrHeaders = array();
    $arrHeaders[] = 'X-Rest-ApiKey: ' . $api_key;
    $arrHeaders[] = 'X-Rest-ApiSign: ' . $strSign;

    if ($this->contentType) {
      $arrHeaders[] = 'Content-Type: ' . $this->contentType;
    }

    $resCurl = curl_init(self::HOST . self::PREFIX . $strUrl);
    curl_setopt($resCurl, CURLOPT_HTTPHEADER, $arrHeaders);
    curl_setopt($resCurl, CURLOPT_HEADER, TRUE);
    curl_setopt($resCurl, CURLOPT_RETURNTRANSFER, TRUE);

    if ($strPostData) {
      curl_setopt($resCurl, CURLOPT_POST, TRUE);
      curl_setopt($resCurl, CURLOPT_POSTFIELDS, $strPostData);
    }

    $this->rawResponse = curl_exec($resCurl);
    $this->httpCode = curl_getinfo($resCurl, CURLINFO_HTTP_CODE);

    if ($boolRawResponse) {
      return $this->rawResponse;
    }

    $this->getResponseFromHeaders($resCurl);
    $this->errors = $this->response['errors'];

    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  private function getResponseFromHeaders($resCurl) {
    $header_size = curl_getinfo($resCurl, CURLINFO_HEADER_SIZE);
    $header = substr($this->rawResponse, 0, $header_size);
    $typePatern = '/Content-Type:\s*([a-z-Z\/]*)\s/';
    preg_match($typePatern, $header, $responseType);
    if (strtolower($responseType[1]) == 'application/zip') {
      $filePatern = '/filename\=\"([a-zA-Z0-9\.]+)\"/';
      preg_match($filePatern, $header, $fileName);
      file_put_contents(self::defaultFilePath . $fileName[1], substr($this->rawResponse, $header_size));
      $this->response = array('path' => self::defaultFilePath . $fileName[1]);
    }
    else {
      $this->response = json_decode(substr($this->rawResponse, $header_size), TRUE);
    }
    return $this->response;
  }

}
