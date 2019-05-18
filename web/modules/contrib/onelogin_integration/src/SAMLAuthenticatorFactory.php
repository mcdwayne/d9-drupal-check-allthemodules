<?php

namespace Drupal\onelogin_integration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\MissingDependencyException;
use Drupal\Core\Url;
use OneLogin\Saml2\Auth;

/**
 * Class SamlAuthenticatorFactory.
 *
 * @package Drupal\onelogin_integration
 */
class SAMLAuthenticatorFactory implements SAMLAuthenticatorFactoryInterface {

  /**
   * The variable that holds an instance of ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * Class atribute to hold an instance of Auth object.
   *
   * @var \OneLogin\Saml2\Auth
   */
  private $auth;

  /**
   * SamlAuthenticatorFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Reference to ConfigFactoryInterface.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    // Check if OneLogin SAML library is installed.
    if (!class_exists('\OneLogin\Saml2\Auth')) {
      throw new MissingDependencyException('The Onelogin Saml2 plugin is not correctly configured');
    }
  }

  /**
   * Settings for the Auth library.
   *
   * Creates an instance of the Auth library with default and,
   * if given, custom settings.
   *
   * @param array $settings
   *   Custom settings for the initialization of the Auth
   *   library.
   *
   * @return \OneLogin\Saml2\Auth
   *   Returns a new instance of the Auth library.
   *
   * @throws \OneLogin\Saml2\Error
   *   Throws Saml2 error.
   */
  public function createFromSettings(array $settings = []) {
    return $this->getAuth($this->configFactory, $settings);
  }

  /**
   * Get auth object from provided settings.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Instance of ConfigFactory interface.
   * @param array $settings
   *   An array of settings.
   *
   * @return \OneLogin\Saml2\Auth
   *   Return an instance of Auth.
   *
   * @throws \OneLogin\Saml2\Error
   *   Throws Saml2 error.
   */
  private function getAuth(ConfigFactoryInterface $configFactory, array $settings = []) {

    if ($this->auth instanceof Auth) {
      return $this->auth;
    }

    $default_settings = [
      'strict' => $configFactory->get('onelogin_integration.settings')->get('strict_mode'),
      'debug' => $configFactory->get('onelogin_integration.settings')->get('debug'),

      'sp' => [
        'entityId' => $configFactory->get('onelogin_integration.settings')->get('sp_entity_id'),
        'assertionConsumerService' => [
          'url' => Url::fromRoute('onelogin_integration.acs', [], ['absolute' => TRUE])->toString(),
        ],
        'singleLogoutService' => [
          'url' => Url::fromRoute('onelogin_integration.slo', [], ['absolute' => TRUE])->toString(),
        ],
        'NameIDFormat' => $configFactory->get('onelogin_integration.settings')->get('nameid_format'),
        'x509cert' => $configFactory->get('onelogin_integration.settings')->get('sp_x509cert'),
        'privateKey' => $configFactory->get('onelogin_integration.settings')->get('sp_privatekey'),
      ],

      'idp' => [
        'entityId' => $configFactory->get('onelogin_integration.settings')->get('entityid'),
        'singleSignOnService' => [
          'url' => $configFactory->get('onelogin_integration.settings')->get('sso'),
        ],
        'singleLogoutService' => [
          'url' => $configFactory->get('onelogin_integration.settings')->get('slo'),
        ],
        'x509cert' => $configFactory->get('onelogin_integration.settings')->get('x509cert'),
      ],

      'security' => [
        'signMetadata' => FALSE,
        'nameIdEncrypted' => $configFactory->get('onelogin_integration.settings')->get('nameid_encrypted'),
        'authnRequestsSigned' => $configFactory->get('onelogin_integration.settings')->get('authn_request_signed'),
        'logoutRequestSigned' => $configFactory->get('onelogin_integration.settings')->get('logout_request_signed'),
        'logoutResponseSigned' => $configFactory->get('onelogin_integration.settings')->get('logout_response_signed'),
        'wantMessagesSigned' => $configFactory->get('onelogin_integration.settings')->get('want_message_signed'),
        'wantAssertionsSigned' => $configFactory->get('onelogin_integration.settings')->get('want_assertion_signed'),
        'wantAssertionsEncrypted' => $configFactory->get('onelogin_integration.settings')->get('want_assertion_encrypted'),
        'relaxDestinationValidation' => TRUE,
      ],
    ];

    $settings = NestedArray::mergeDeep($default_settings, $settings);

    $this->auth = new Auth($settings);

    return $this->auth;
  }

}
