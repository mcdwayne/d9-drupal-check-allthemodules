<?php

namespace Drupal\certificatelogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginManager;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;

/**
 * Class CertificateLoginController.
 *
 * @package Drupal\certificatelogin\Controller
 */
class CertificateLoginController extends ControllerBase {

  const AUTHENTICATION_PROVIDER_NAME = 'certificatelogin';
  const AUTHENTICATION_NAME_DELIMITER = '|';

  /**
   * External Authentication's map between local users and service users.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  /**
   * External Authentication's service for authenticating users.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalauth;

  /**
   * The plugin manager for CA certificate verification.
   *
   * @var \Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginManager
   */
  protected $caCertificateVerificationService;

  /**
   * Constructs a new CertificateLoginController object.
   */
  public function __construct(Authmap $externalauth_authmap, ExternalAuth $externalauth_externalauth, CaSignatureVerificationPluginManager $ca_certificate_verification_service) {
    $this->authmap = $externalauth_authmap;
    $this->externalauth = $externalauth_externalauth;
    $this->caCertificateVerificationService = $ca_certificate_verification_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('externalauth.authmap'),
      $container->get('externalauth.externalauth'),
      $container->get('plugin.manager.certificatelogin.ca_signature_verification')
    );
  }

  /**
   * Process a login request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function processLoginRequest() {
    if (!$this->currentUserIsHuman()) {
      return $this->displayErrorAndResetPage($this->t('Certificate logins are only available to humans.'));
    }

    if (!$client_certificate = $this->getClientCertificate()) {
      return $this->displayErrorAndResetPage($this->t('No client certificate found for logging in.'));
    }

    if (!$this->clientCertificateAuthenticates($client_certificate)) {
      return $this->displayErrorAndResetPage($this->t('Your client certificate cannot be used to authenticate to this site.'));
    }

    $this->loginUserWithCertificate($client_certificate);

    return $this->redirect('user.page');
  }

  /**
   * Determine if the current user is human.
   *
   * It's a good idea to check for this so we don't waste resources on bots.
   *
   * @return boolean
   *   TRUE if the current user is human; FALSE otherwise.
   *
   * @todo Implement this, possibly using Antibot's JS test.
   */
  protected function currentUserIsHuman() {
    return TRUE;
  }

  protected function displayErrorAndResetPage($message) {
    drupal_set_message($message, 'error');
    $referrer = \Drupal::request()->headers->get('referer');
    return $this->redirect(Url::fromUserInput(parse_url($referrer)['path'])->getRouteName());
  }

  protected function getClientCertificate() {
    $server_variable = $this->config('certificatelogin.settings')->get('client_certificate_server_variable');
    return \Drupal::request()->server->get($server_variable);
  }

  /**
   * Determines if the client certificate can be used for authentication.
   *
   * We only accept certificates signed by the CA whose certificate was provided
   * in the module's settings. However, if a CA wasn't provided, we'll accept
   * any certificate as valid authentication.
   *
   * @param x509cert $client_certificate
   *   The client certificate provided by the user on attempting to log in.
   *
   * @return boolean
   *   TRUE if the certificate authenticates; FALSE otherwise.
   */
  protected function clientCertificateAuthenticates($client_certificate) {
    if (!$ca_certificate = $this->config('certificatelogin.settings')->get('ca_certificate')) {
      return TRUE;
    }

    if ($this->clientCertificateSignedByAuthority($client_certificate, $ca_certificate)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if a client certificate has been signed by a particular CA.
   *
   * @param x509cert $client_certificate
   *   The client certificate.
   * @param x509cert $ca_certificate
   *   The certification authority (CA) certificate.
   *
   * @return boolean
   *   TRUE if the client certificate was signed by the CA; FALSE otherwise.
   */
  protected function clientCertificateSignedByAuthority($client_certificate, $ca_certificate) {
    $plugin_id = $this->config('certificatelogin.settings')->get('ca_signature_verifier');
    $signature_verifier = $this->caCertificateVerificationService->createInstance($plugin_id);
    return $signature_verifier->clientCertificateSignedByAuthority($client_certificate, $ca_certificate);
  }

  /**
   * Log the user in, creating the account if it doesn't exist yet.
   *
   * @param x509cert $client_certificate
   *   The client certificate.
   *
   * @return \Drupal\user\UserInterface
   *   The registered Drupal user.
   */
  protected function loginUserWithCertificate($client_certificate) {
    $certificate_info = openssl_x509_parse($client_certificate, FALSE);
    $provider = $this->getAuthenticationProviderName();
    $authname = $this->getAuthenticationUserName($certificate_info);
    $account_data = $this->getUserAccountData($certificate_info);

    if (!$this->authmap->getUid($authname, $provider)) {
      return $this->externalauth->loginRegister($authname, $provider, $account_data, $client_certificate);
    }
    return $this->externalauth->login($authname, $provider);
  }

  public static function getAuthenticationProviderName() {
    return self::AUTHENTICATION_PROVIDER_NAME;
  }

  protected function getAuthenticationUserName($certificate_info) {
    $issuer_common_name = $certificate_info['issuer']['commonName'];
    $distinguished_name = $certificate_info['name'];
    return $issuer_common_name . $this->getAuthenticationNameDelimiter() . $distinguished_name;
  }

  public static function getAuthenticationNameDelimiter() {
    return self::AUTHENTICATION_NAME_DELIMITER;
  }

  protected function getUserAccountData($certificate_info) {
    return [
      'name' => $certificate_info['subject']['commonName'],
      'mail' => $certificate_info['subject']['commonName'],
    ];
  }

}
