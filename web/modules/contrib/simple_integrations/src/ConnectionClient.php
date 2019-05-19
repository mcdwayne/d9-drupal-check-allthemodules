<?php

namespace Drupal\simple_integrations;

use GuzzleHttp\Client;
use Drupal\simple_integrations\Exception\CertificateFileNotFoundException;
use Drupal\simple_integrations\Exception\IntegrationInactiveException;

/**
 * The connection client.
 *
 * Acts as an extension of core's http_client service, with additional
 * functionality to configure the request automatically.
 */
class ConnectionClient extends Client {

  /**
   * The associated Integration.
   *
   * @var \Drupal\simple_integrations\IntegrationInterface
   */
  private $integration;

  /**
   * The authentication type.
   *
   * @var string
   */
  private $authType;

  /**
   * The location of a certificate.
   *
   * @var string
   */
  private $certificate;

  /**
   * The authentication username.
   *
   * @var string
   */
  private $authUser;

  /**
   * The authentication password or key.
   *
   * @var string
   */
  private $authKey;

  /**
   * The request end point.
   *
   * @var string
   */
  private $endPoint;

  /**
   * Config for the connection.
   *
   * @var array
   */
  private $requestConfig = [];

  /**
   * Perform a callback.
   *
   * @param string $method
   *   The method, eg GET or POST.
   * @param array $args
   *   An array of arguments, with a URI first and an array of config second.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @throws \Drupal\simple_integrations\Exception\IntegrationInactiveException
   * @throws \GuzzleHttp\Exception\ClientException
   */
  public function __call($method, $args) {
    if (!$this->integration->isActive()) {
      throw new IntegrationInactiveException($this->integration->id());
    }

    return parent::__call($method, $args);
  }

  /**
   * Set the integration entity.
   *
   * @param \Drupal\simple_integrations\IntegrationInterface $integration
   *   The integration entity.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   */
  public function setIntegration(IntegrationInterface $integration) {
    $this->integration = $integration;
    return $this;
  }

  /**
   * Get the integration entity.
   *
   * @return \Drupal\simple_integrations\IntegrationInterface
   *   The integration entity.
   */
  public function getIntegration() {
    return $this->integration;
  }

  /**
   * Perform configuration tasks.
   */
  public function configure() {
    // Configure the authentication. Always add these values, even if they're
    // blank.
    $this
      ->setAuthType()
      ->setAuthValues()
      ->setCertificate();

    // Configure the request end point.
    $this->setRequestEndPoint();

    // Add the credentials to the request, if appropriate.
    if ($this->getAuthType() != 'none') {
      $this->setCredentials();
    }
  }

  /**
   * Get the configuration for a request.
   *
   * @return array
   *   The configuration.
   */
  public function getRequestConfig() {
    return $this->requestConfig;
  }

  /**
   * Set authentication type from a given integration.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   */
  public function setAuthType() {
    $this->authType = $this->integration->get('auth_type');
    return $this;
  }

  /**
   * Get the authentication type.
   *
   * @return string
   *   The auth type.
   */
  public function getAuthType() {
    return $this->authType;
  }

  /**
   * Set the authentication values from a given integration.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   */
  public function setAuthValues() {
    $this->authUser = $this->integration->get('auth_user');
    $this->authKey = $this->integration->get('auth_key');
    return $this;
  }

  /**
   * Set a certificate.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   *
   * @throws \Drupal\simple_integrations\Exception\CertificateFileNotFoundException
   */
  public function setCertificate() {
    $this->certificate = $this->integration->get('certificate');

    if (!empty($this->getCertificate())) {
      // If there is a certificate, make sure the file exists.
      if (!file_exists($this->getCertificate())) {
        throw new CertificateFileNotFoundException();
      }
    }

    return $this;
  }

  /**
   * Get the authentication user.
   *
   * @return string
   *   The auth user.
   */
  public function getAuthUser() {
    return $this->authUser;
  }

  /**
   * Get the authentication key.
   *
   * @return string
   *   The auth key.
   */
  public function getAuthKey() {
    return $this->authKey;
  }

  /**
   * Get the certificate file.
   *
   * @return string
   *   The certificate file.
   */
  public function getCertificate() {
    return $this->certificate;
  }

  /**
   * Set the end point for this request.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   */
  public function setRequestEndPoint() {
    $this->endPoint = $this->integration->get('external_end_point');
    return $this;
  }

  /**
   * Get the end point for this request.
   *
   * @return string
   *   The end point.
   */
  public function getRequestEndPoint() {
    return $this->endPoint;
  }

  /**
   * Add credentials to a request.
   *
   * @return \Drupal\simple_integrations\ConnectionClient
   *   Return this object.
   */
  public function setCredentials() {
    if ($this->getAuthType() == 'headers') {
      // Add authentication headers.
      $this->requestConfig['headers']['Authorization'] = [
        $this->getAuthUser(),
        $this->getAuthKey(),
      ];
    }
    elseif ($this->getAuthType() == 'basic_auth') {
      // Add basic authentication.
      $this->requestConfig['auth'] = [
        $this->getAuthUser(),
        $this->getAuthKey(),
      ];
    }
    elseif ($this->getAuthType() == 'certificate') {
      // Add the certificate.
      $this->requestConfig['cert'] = [
        $this->getCertificate(),
        $this->getAuthKey(),
      ];
    }

    return $this;
  }

}
