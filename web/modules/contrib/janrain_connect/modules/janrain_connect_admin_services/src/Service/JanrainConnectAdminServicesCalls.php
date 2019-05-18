<?php

namespace Drupal\janrain_connect_admin_services\Service;

use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\janrain_connect_super_admin\Service\JanrainConnectSuperAdminServices;

/**
 * Class: JanrainConnectAdminServicesCalls.
 */
class JanrainConnectAdminServicesCalls {

  use StringTranslationTrait;

  /**
   * JanrainConnectConnector.
   *
   * @var \Drupal\janrain_connect\Service\JanrainConnectConnector
   */
  protected $janrainConnector;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * JanrainConnectSuperAdminServices.
   *
   * @var \Drupal\janrain_connect_super_admin\Service\JanrainConnectSuperAdminServices
   */
  protected $janrainConnectSuperAdminServices;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger_factory, JanrainConnectConnector $janrain_connector, JanrainConnectSuperAdminServices $janrain_connect_super_admin_services) {
    $this->config = $config_factory->get('janrain_connect_super_admin.settings');
    $this->logger = $logger_factory->get('janrain_connect');
    $this->janrainConnector = $janrain_connector;
    $this->janrainConnectSuperAdminServices = $janrain_connect_super_admin_services;
  }

  /**
   * Find User.
   *
   * @param string $filter
   *   Filter to find user.
   *
   * @return array|bool
   *   Janrain api return or FALSE.
   */
  public function findUser($filter) {
    $direct_access_values = $this->janrainConnectSuperAdminServices->getConfigDirectAccess();

    if (empty($direct_access_values['direct_access_id']) || empty($direct_access_values['direct_access_secret'])) {
      return FALSE;
    }

    $auth_id = $direct_access_values['direct_access_id'];
    $auth_secret = $direct_access_values['direct_access_secret'];

    $response = $this->janrainConnector->findUser(
      $filter,
      $auth_id,
      $auth_secret
    );

    return (!empty($response[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_HAS_ERRORS])) ?
      FALSE : $response;
  }

  /**
   * Entity Delete on Janrain.
   *
   * @param string $uuid
   *   The unique identifier given to the entity.
   *
   * @return array
   *   Janrain api return.
   */
  public function entityDelete($uuid) {
    $result = $this->janrainConnector->janrainApi->entityDelete(
      $uuid,
      $this->janrainConnector->config->get('entity_type')
    );

    $this->janrainConnector->janrainConnectValidate->validateResponse($result);

    return $result;
  }

}
