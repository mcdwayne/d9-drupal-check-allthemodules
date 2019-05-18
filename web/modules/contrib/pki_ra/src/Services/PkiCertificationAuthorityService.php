<?php

namespace Drupal\pki_ra\Services;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;

class PkiCertificationAuthorityService extends PkiService {

  const CERTIFICATES = 'certificates';
  const AUTHENTICATION_DELIMITER = ': ';

  protected $authentication;

  public function __construct($data_to_send) {
    parent::__construct($data_to_send);
    $this->authentication = $this->getServiceAuthentication();
  }

  /**
   * @todo Verify that the CA is a trusted host using Symfony\Component\HttpFoundation\Request.
   */
  protected static function getServiceUrl() {
    $url = \Drupal::config('pki_ra.settings')->get('certificate_authority_url');

    if (!UrlHelper::isValid($url, TRUE)) {
      throw new \Exception('The certificate authority (CA) URL was not entered on the PKI Registration Authority settings page.');
    }

    return $url . '/' . self::CERTIFICATES;
  }

  protected static function getServiceAuthentication() {
    $authentication = \Drupal::config('pki_ra.settings')->get('certificate_authority_authentication_header');

    if (!empty($authentication) && !self::authenticationIsProperlyFormatted($authentication)) {
      throw new \Exception('The certificate authority (CA) authentication header was not formatted correctly.');
    }

    return $authentication;
  }

  public static function authenticationIsProperlyFormatted($authentication) {
    if ((Html::escape($authentication) == $authentication) &&
        (count(explode(self::AUTHENTICATION_DELIMITER, $authentication)) == 2)) {
      return TRUE;
    }
    return FALSE;
  }

  public function forwardCertificateSigningRequest() {
    \Drupal::logger('pki_ra')->notice('Sending CSR to CA from registrant with e-mail address %email.', [
      '%email' => $this->data_to_send['email'],
    ]);

    $this->sendRequest();

    \Drupal::logger('pki_ra')->notice('CA at %url responded to CSR with status %status and headers %headers.', [
      '%url' => Xss::filterAdmin($this->url),
      '%status' => $this->getResponseStatus(),
      '%headers' => serialize($this->getResponseHeaders()),
    ]);

    return $this;
  }

  protected function getDataToSend() {
    $data = $this->data_to_send;

    if (is_null($data['score'])) {
      unset($data['score']);
    }

    return $data;
  }

  protected function getHeadersToSend() {
    if (empty($this->authentication)) {
      return [];
    }
    return [$this->getAuthenticationHeaderKey() => $this->getAuthenticationHeaderValue()];
  }

  protected function getAuthenticationHeaderKey() {
    return $this->getAuthenticationHeaderElement(0);
  }

  protected function getAuthenticationHeaderValue() {
    return $this->getAuthenticationHeaderElement(1);
  }

  protected function getAuthenticationHeaderElement($elementId) {
    return trim(explode(self::AUTHENTICATION_DELIMITER, $this->authentication)[$elementId]);
  }

}
