<?php

namespace Drupal\just_giving;

/**
 * Class JustGivingClient.
 */
class JustGivingClient implements JustGivingClientInterface {

  private $justGivingClient;

  private $rootDomain;

  private $apiKey;

  private $apiVersion;

  private $username;

  private $password;

  /**
   * Main call for just giving client.
   *
   * @return bool|mixed
   *   Returns just giving client
   */
  public function jgLoad() {
    $this->justGivingClient = $this->loadJustGivingClient();
    return $this->justGivingClient;
  }

  /**
   * Users account name.
   *
   * @param mixed $username
   *   Sting value.
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * @param mixed $password
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * @return bool
   */
  private function loadJustGivingClient() {

    $config = \Drupal::config('just_giving.justgivingconfig');
    $this->rootDomain = $config->get('environments');
    $this->apiKey = $config->get('api_key');
    $this->apiVersion = $config->get('api_version');
    if (!isset($this->username) && !isset($this->password)) {
      $this->username = NULL;
      $this->password = NULL;
    }
    if ($this->rootDomain
      && $this->apiKey
      && $this->apiVersion) {
      try {
        $this->justGivingClient = new \JustGivingClient($this->rootDomain,
          $this->apiKey,
          $this->apiVersion,
          $this->username,
          $this->password);
        return $this->justGivingClient;
      }
      catch (Exception $e) {
        \Drupal::logger('just_giving')->error($e->getMessage());
        return FALSE;
      }
    }
    else {
      $message = "Missing Configuration: admin/config/just_giving/justgivingconfig";
      \Drupal::logger('just_giving')->notice($message);
      return FALSE;
    }
  }

}

