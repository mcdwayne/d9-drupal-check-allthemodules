<?php

namespace Drupal\httpcsvalidation\Helper;

/**
 * Httpcs form helper.
 */
class HttpcsFormHandler {

  private $url         = "";
  private $function    = "";
  private $company     = "";
  private $name        = "";
  private $phone       = "";
  private $email       = "";
  private $password    = "";
  private $secondToken = "";
  private $event       = "";

  /**
   * Method handleForm.
   */
  public function handleForm($params) {
    global $base_url;
    $this->url = $base_url;

    if (isset($params) && !empty($params)) {
      if (isset($params['httpcs_crea']) && $params['httpcs_crea'] === 'Y') {

        // Sanitizing post variables.
        $args           = [
          'httpcs_crea' => FILTER_SANITIZE_ENCODED,
          'url'         => FILTER_SANITIZE_URL,
          'name'        => FILTER_SANITIZE_ENCODED,
          'function'    => FILTER_SANITIZE_ENCODED,
          'company'     => FILTER_SANITIZE_ENCODED,
          'phone'       => FILTER_SANITIZE_ENCODED,
          'email'       => FILTER_SANITIZE_EMAIL,
        ];
        $tabFormContent = filter_input_array(INPUT_POST, $args);

        if ($tabFormContent && !empty($tabFormContent)) {
          if (filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->url = $this->url;
          }
          if (filter_var($tabFormContent['email'], FILTER_VALIDATE_EMAIL)) {
            $this->email = $tabFormContent['email'];
          }
          $this->name     = $tabFormContent['name'];
          $this->function = $tabFormContent['function'];
          $this->company  = $tabFormContent['company'];
          $this->phone    = $tabFormContent['phone'];
        }

        // Curl to send data to HTTPCS' service.
        if (!empty($this->url) && !empty($this->email) && !empty($this->name) && !empty($this->function) && !empty($this->company) && !empty($this->phone)) {
          return $this;
        }
        else {
          return [];
        }
      }

      // Connexion form handler.
      if (isset($params['httpcs_co']) && $params['httpcs_co'] === 'Y') {
        // Sanitizing post variables.
        $args           = [
          'httpcs_co' => FILTER_SANITIZE_ENCODED,
          'url'       => FILTER_SANITIZE_URL,
          'email'     => FILTER_SANITIZE_EMAIL,
          'password'  => FILTER_SANITIZE_ENCODED,
        ];
        $tabFormContent = filter_input_array(INPUT_POST, $args);

        if ($tabFormContent && !empty($tabFormContent)) {
          if (filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->url = $this->url;
          }
          if (filter_var($tabFormContent['email'], FILTER_VALIDATE_EMAIL)) {
            $this->email = $tabFormContent['email'];
          }
          $this->password = $tabFormContent['password'];
        }
        // Curl to send data to HTTPCS' service.
        if (!empty($this->url) && !empty($this->email) && !empty($this->password)) {
          return $this;
        }
        else {
          return [];
        }
      }

      // Retry check form handler.
      if (isset($params['httpcs_retry']) && $params['httpcs_retry'] === 'Y') {
        // Sanitizing post variables.
        $args           = [
          'httpcs_retry' => FILTER_SANITIZE_ENCODED,
          'url'          => FILTER_SANITIZE_URL,
          'email'        => FILTER_SANITIZE_EMAIL,
          'secondToken'  => FILTER_SANITIZE_ENCODED,
          'event'        => FILTER_SANITIZE_ENCODED,
        ];
        $tabFormContent = filter_input_array(INPUT_POST, $args);

        if ($tabFormContent && !empty($tabFormContent)) {
          if (filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->url = $this->url;
          }
          if (filter_var($tabFormContent['email'], FILTER_VALIDATE_EMAIL)) {
            $this->email = $tabFormContent['email'];
          }
          $this->secondToken = $tabFormContent['secondToken'];
          $this->event       = $tabFormContent['event'];
        }

        // Curl to send data to HTTPCS' service.
        if (!empty($this->url) && !empty($this->email) && !empty($this->secondToken) && !empty($this->event)) {
          return $this;
        }
        else {
          return [];
        }
      }
    }
  }

  /**
   * Method objToArray().
   */
  public function objToArray() {
    return [
      'url'         => $this->getUrl(),
      'email'       => $this->getEmail(),
      'name'        => $this->getName(),
      'function'    => $this->getFunction(),
      'company'     => $this->getCompany(),
      'phone'       => $this->getPhone(),
      'password'    => $this->getPassword(),
      'secondToken' => $this->getSecondToken(),
      'event'       => $this->getEvent(),
    ];
  }

  /**
   * Method getUrl().
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Method getEMail().
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Method getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Method getFunction().
   */
  public function getFunction() {
    return $this->function;
  }

  /**
   * Method getCompany().
   */
  public function getCompany() {
    return $this->company;
  }

  /**
   * Method getPhone().
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * Method getPassword().
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * Method getSecondToken().
   */
  public function getSecondToken() {
    return $this->secondToken;
  }

  /**
   * Method getEvent().
   */
  public function getEvent() {
    return $this->event;
  }

}
