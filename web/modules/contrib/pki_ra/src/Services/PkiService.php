<?php

namespace Drupal\pki_ra\Services;

abstract class PkiService {

  protected $url;
  protected $data_to_send;
  protected $status;
  protected $headers;
  protected $response;

  abstract protected static function getServiceUrl();
  abstract protected function getHeadersToSend();
  abstract protected function getDataToSend();

  public function __construct($data_to_send) {
    $this->url = $this->getServiceUrl();
    $this->data_to_send = $data_to_send;
  }

  public function sendRequest() {
    if (\Drupal::config('pki_ra.settings')->get('debug')) {
      \Drupal::logger('pki_ra')
        ->debug('Sending POST request to URL %url with headers %headers and data %data.', $this->getSendingDataToLog());
    }

    try {
      $request = \Drupal::httpClient()->post($this->url, [
        'headers' => $this->getHeadersToSend(),
        'json' => $this->getDataToSend(),
      ]);
    }
    catch (Exception $e) {
      watchdog_exception('pki_ra', $e->getMessage());
    }

    $this->status = $request->getStatusCode();
    $this->headers = $request->getHeaders();
    $this->response = json_decode((string) $request->getBody(), TRUE);

    if (\Drupal::config('pki_ra.settings')->get('debug')) {
      \Drupal::logger('pki_ra')
        ->debug('Received reply from URL %url with status code %code, headers %headers and data %data.', $this->getReceivingDataToLog());
    }

    return $this;
  }

  protected function getSendingDataToLog() {
    return [
      '%url' => $this->url,
      '%headers' => serialize($this->getHeadersToSend()),
      '%data' => serialize($this->getDataToSend()),
    ];
  }

  protected function getReceivingDataToLog() {
    return [
      '%url' => $this->url,
      '%code' => $this->status,
      '%headers' => serialize($this->headers),
      '%data' => serialize($this->response),
    ];
  }

  public function getResponseData() {
    return isset($this->response) ? $this->response : 'NO DATA';
  }

  public function getResponseHeaders() {
    return isset($this->headers) ? $this->headers : 'NO HEADERS';
  }

  public function getResponseStatus() {
    return isset($this->status) ? $this->status : 'NO STATUS';
  }

}
