<?php

namespace Drupal\social_auth_esia\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_esia\Settings\EsiaAuthSettings;
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\Signer\OpensslPkcs7;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a Network Plugin for Social Auth ESIA.
 *
 * @Network(
 *   id = "social_auth_esia",
 *   social_network = "ESIA",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_esia\Settings\EsiaAuthSettings",
 *       "config_id": "social_auth_esia.settings"
 *     }
 *   }
 * )
 */
class EsiaAuth extends NetworkBase implements EsiaAuthInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * EsiaAuth constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactoryInterface $logger_factory,
                              Request $request) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->loggerFactory = $logger_factory;
    $this->request = $request;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \Ekapusta\OAuth2Esia\Provider\EsiaProvider
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {
    $class_name = '\Ekapusta\OAuth2Esia\EsiaService';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for ESIA OAuth 2.0 could not be found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_esia\Settings\EsiaAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      $scopes = $settings->getScopes();
      $custom_scopes = explode(',', preg_replace('/\s+/', '', $scopes));
      $default_scopes = ['email'];

      $esia_settings = [
        'clientId' => $settings->getClientId(),
        'redirectUri' => $this->request->getSchemeAndHttpHost() . '/user/login/esia/callback',
        'defaultScopes' => array_unique(array_merge($default_scopes, $custom_scopes)),
      ];

      if ($settings->getUseTestingServer()) {
        $esia_settings['remoteUrl'] = 'https://esia-portal1.test.gosuslugi.ru';
        $esia_settings['remoteCertificatePath'] = EsiaProvider::RESOURCES . 'esia.test.cer';
      }

      $collaborators = [
        'signer' => new OpensslPkcs7(
          DRUPAL_ROOT . $settings->getCertificatePath(),
          DRUPAL_ROOT . $settings->getPrivateKeyPath(),
          $settings->getPrivateKeyPassword()
        ),
      ];

      return new EsiaProvider($esia_settings, $collaborators);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_esia\Settings\EsiaAuthSettings $settings
   *   The ESIA auth settings.
   *
   * @return bool
   *   TRUE if module is configured.
   *   FALSE otherwise.
   */
  protected function validateConfig(EsiaAuthSettings $settings) {

    $client_id = $settings->getClientId();
    if (!$client_id) {
      $this->loggerFactory
        ->get('social_auth_esia')
        ->error('Define Client ID on module settings.');
      return FALSE;
    }

    $certificate_path = $settings->getCertificatePath();
    if (!file_exists(DRUPAL_ROOT . $certificate_path)) {
      $this->loggerFactory
        ->get('social_auth_esia')
        ->error('Certificate file does not exist. Check your path settings or file.');
      return FALSE;
    }

    $private_key_path = $settings->getPrivateKeyPath();
    if (!file_exists(DRUPAL_ROOT . $private_key_path)) {
      $this->loggerFactory
        ->get('social_auth_esia')
        ->error('Private key file does not exist. Check your path settings or file.');
      return FALSE;
    }

    return TRUE;
  }

}
