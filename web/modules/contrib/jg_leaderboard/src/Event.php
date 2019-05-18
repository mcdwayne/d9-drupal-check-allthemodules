<?php

namespace Drupal\jg_leaderboard;


/**
 * Class Event
 *
 * @package Drupal\jg_leaderboard
 */
class Event extends JGClient {
  public  $eventName;
  public  $eventDescription;
  public  $eventCompletionDate;
  public  $eventExpiryDate;
  public  $eventStartDate;
  public  $eventType;
  public  $eventLocation;
  private $userName;
  private $password;

  /**
   * @param $userName
   */
  public function setUserName($userName) {
    $this->userName = $userName;
  }

  /**
   * @param $password
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * @return mixed
   */
  public function getUserName() {
    return $this->userName;
  }

  /**
   * @return mixed
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * @param        $url
   * @param string $base64Credentials
   * @param        $payload
   * @param string $contentType
   *
   * @return mixed
   */
  public function postAndGetResponse($url, $base64Credentials = "", $payload, $contentType = "application/json") {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $this->SetCredentials($ch, $base64Credentials, $contentType);

    $buffer = curl_exec($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);

    return $buffer;
  }

  /**
   * @param        $ch
   * @param string $base64Credentials
   * @param string $contentType
   */
  private function SetCredentials($ch, $base64Credentials = "", $contentType = "application/json") {
    if ($base64Credentials != NULL && $base64Credentials != "") {
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-type: ' . $contentType,
        'Accept: ' . $contentType,
        'Authorize: Basic ' . $base64Credentials,
        'Authorization: Basic ' . $base64Credentials
      ]);
    }
    else {
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-type: ' . $contentType,
        'Accept: ' . $contentType
      ]);
    }
  }

  /**
   * @return string
   */
  public function buildAuthenticationValue() {
    if ($this->userName != NULL && $this->userName != "") {
      $stringForEnc = $this->userName . ":" . $this->password;

      return base64_encode($stringForEnc);
    }

    return "";
  }

  /**
   * @param $event
   *
   * @return mixed
   */
  public function createEvent($event) {
    //@todo get the envirnoment from the form settings
    $eventUri = "https://api.justgiving.com/" . "{apiKey}/v{apiVersion}/event";
    $url      = $this->buildUrl($eventUri);

    $payload = json_encode($event);
    $json    = $this->postAndGetResponse($url, $this->buildAuthenticationValue(), $payload);
    $json    = json_decode($json);

    if (isset($json->id)) {
      return $json;
    }
    else {
      return $json[0]->id;
    }
  }
}
