<?php

namespace Drupal\webform_epetition;

/**
 * Class WebformEpetitionFormat.
 */
class WebformEpetitionFormat implements WebformEpetitionFormatInterface {

  protected $response;

  protected $dataType;

  protected $apiUrl;


  public function __construct() {
    $config = \Drupal::config('webform_epetition.webformepetitionconfig');
    $this->apiUrl = $config->get('api_url');
  }

  /**
   * @param $response
   */
  public function setResponse($response): void {
    $this->response = json_decode($response);
  }

  /**
   * @param mixed $dataType
   */
  public function setDataType($dataType): void {
    $this->dataType = $dataType;
  }

  /**
   * @return string
   */
  public function getDetails() {
    $output = '';
    if (isset($this->response->error)) {
      $output = '<h4>' . $this->response->error . ', try another.</h4>';
    }
    elseif ($this->dataType == 'getMP') {
      $output = $this->createListing($this->response);
    }
    elseif ($this->dataType == 'getMSP' || $this->dataType == 'getMLA') {
    foreach ($this->response as $value) {
      $output .= $this->createListing($value);
      }
    }
    return $output;
  }

  /**
   * @return string
   */
  public function getEmails() {

    $emails = '';
    $count = count($this->response);
    $counter = 1;
    if (isset($this->response->error) && !is_array($this->response->error)) {
      $emails = $this->response->error;
    }
    elseif ($this->dataType == 'getMP') {
      $emails = $this->createEmail($this->response->given_name, $this->response->family_name);
    }
    elseif ($this->dataType == 'getMSP' || $this->dataType == 'getMLA') {
    foreach ($this->response as $value) {
        $emails .= $this->createEmail($value->given_name, $value->family_name);
        if ($counter < $count) {
          $emails .= ',';
        }
      $counter++;
      }
    }
    return $emails;

  }

  /**
   * @return string
   */
  public function getNames() {

    $names = '';
    $count = count($this->response);
    $counter = 1;
    if (isset($this->response->error) && !is_array($this->response->error)) {
      $names = $this->response->error;
    }
    elseif ($this->dataType == 'getMP') {
      $names = $this->response->full_name;
    }
    elseif ($this->dataType == 'getMSP' || $this->dataType == 'getMLA') {
      foreach ($this->response as $value) {
        $names .= $value->full_name;
        if ($counter < $count) {
          $names .= ':';
        }
        $counter++;
      }
    }
    return $names;

  }

  /**
   * @param $data
   *
   * @return string
   */
  private function createListing($data) {

    $output = '<h4>' . $data->full_name . '</h4>';
    if (isset($data->image)) {
      $output .= '<div><img src="' . $this->apiUrl . $data->image . '" /></div>';
    }
    $output .= '<div>Party: <b>' . $data->party . '</b></div>';
    $output .= '<div>Constituency: <b>' . $data->constituency . '</b></div>';
    return $output;

  }

  /**
   * @param $firstName
   * @param $lastName
   *
   * @return string
   */
  private function createEmail($firstName, $lastName) {

    $email = '';
    switch ($this->dataType) {
      case 'getMP':
        $email = $firstName . $lastName . 'mp@parliament.uk';
        break;
      case 'getMSP':
        $email = $firstName . '.' . $lastName . '.msp@parliament.scot';
        break;
      case 'getMLA':
        $email = $firstName . '.' . $lastName . '@mla.niassembly.gov.uk';
        break;
    }
    return $email;

  }


}
