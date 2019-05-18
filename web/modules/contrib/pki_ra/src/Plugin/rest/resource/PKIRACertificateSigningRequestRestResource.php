<?php

namespace Drupal\pki_ra\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\pki_ra\Processors\PKIRACertificateProcessor;
use Drupal\pki_ra\Processors\PKIRARegistrationProcessor;
use Drupal\pki_ra\Services\PkiCertificationAuthorityService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a resource to get a certificate from a certification authority.
 *
 * @RestResource(
 *   id = "pki_ra_certificate_signing_request_rest_resource",
 *   label = @Translation("Certificate signing request"),
 *   uri_paths = {
 *     "canonical" = "/certificate/signing-request",
 *     "https://www.drupal.org/link-relations/create" = "/certificate/signing-request"
 *   }
 * )
 */
class PKIRACertificateSigningRequestRestResource extends ResourceBase {

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('pki_ra'),
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Handles certificate signing requests (CSRs) by forwarding to a CA, and
   * returning the resulting certificate.
   *
   * @param array $data
   *   * registrationId: The user's registration ID.
   *   * csr: A string representing the CSR itself.
   *   * profile: A string representing the type of certificate requested.
   *
   * @todo Support full PKCS#10 specification: https://tools.ietf.org/html/rfc2986
   */
  public function post($data = []) {

    if (!isset($data['registrationId']) || !$this->requestIsAuthenticated($data['registrationId'])) {
      $this->logger->notice('CSR received, but request failed to authenticate.  Data: %data', ['%data' => serialize($data)]);
      return new ResourceResponse('Cannot find valid security token matching provided registration ID.  Cannot process CSR.', 403);
    }

    if (!isset($data['csr']) || !is_string($data['csr'])) {
      $this->logger->notice('Invalid CSR received with data %data', ['%data' => serialize($data)]);
      return new ResourceResponse('Must be passed as a string within the data array: csr', 400);
    }
    if (!isset($data['profile']) || !is_string($data['profile'])) {
      $this->logger->notice('Invalid CSR received with data %data', ['%data' => serialize($data)]);
      return new ResourceResponse('Must be passed as a string within the data array: profile', 400);
    }

    $this->logger->notice('Valid CSR received from registrant ID #%id with CSR %csr and profile %profile.', [
      '%id' => $data['registrationId'],
      '%csr' => $data['csr'],
      '%profile' => $data['profile'],
    ]);

    $registration_id = $data['registrationId'];
    unset($data['registrationId']);
    return $this->sendRequestToCaAndProcessResults($registration_id, $data);
  }

  /**
   * Check if the request is authenticated.
   *
   * @todo Replace this with a formal token-based Authentication Provider plug-in.
   */
  protected function requestIsAuthenticated($registration_id) {
    $security_token_key = 'pki_ra_csr_token_' . $registration_id;
    $registration = Node::load($registration_id);
    $registration_code = $registration->get('field_registration_code')->getValue()[0]['value'];

    if (!isset($_SESSION[$security_token_key]) ||
        !is_object($registration) ||
        ($registration->getType() != PKIRARegistrationProcessor::NODE_TYPE) ||
        !PKIRARegistrationProcessor::isConfirmed($registration) ||
        !isset($registration_code)) {
      return FALSE;
    }

    if ($_SESSION[$security_token_key] != $registration_code) {
      return FALSE;
    }

    unset($_SESSION[$security_token_key]);
    $registration_processor = new PKIRARegistrationProcessor($registration);
    $registration_processor->unsetSecurityToken()->saveRegistration();
    return TRUE;
  }

  /**
   * @todo Once the client JS is sending the proper profile, we can stop
   *   overriding it here by reverting to $data_from_user['profile'].
   */
  protected function sendRequestToCaAndProcessResults($registration_id, $data_from_user) {
    $registration = Node::load($registration_id);

    $certification_authority = new PkiCertificationAuthorityService($this->getDataForCsr($data_from_user, $registration));

    $response = $certification_authority->forwardCertificateSigningRequest()->getResponseData();
    $status = $certification_authority->getResponseStatus();
    $headers = $certification_authority->getResponseHeaders();

    if (isset($response['certificate'])) {
      $this->processGeneratedCertificate($registration, $response['certificate'], $status);
    }
    else {
      $this->logger->warning('CA did not return a certificate!');
    }

    return new ResourceResponse($response, $status, $headers);
  }

  protected function processGeneratedCertificate(Node $registration, $certificate, $status) {
    PKIRACertificateProcessor::createCertificate($registration, $certificate)
      ->saveCertificate();
    return $this->invalidateUsedSecurityToken($registration, $status);
  }

  protected function invalidateUsedSecurityToken(Node $registration, $status) {
    $processor = new PKIRARegistrationProcessor($registration);

    if ($status == 201) {
      $processor->unsetSecurityToken()->saveRegistration();
    }

    return $this;
  }

  /**
   * Get data to send to CSR.
   *
   * @param $data_from_user
   * @param $registration
   * @return array
   */
  protected function getDataForCsr($data_from_user, $registration) {
    $data = [
      'csr' => $data_from_user['csr'],
      'profile' => 'authentication',
      'email' => $registration->getTitle(),
    ];
    // Allow modules to add more data variables to this array.
    $this->moduleHandler->alter('pki_ra_csr_data', $data);
    return $data;
  }

}
