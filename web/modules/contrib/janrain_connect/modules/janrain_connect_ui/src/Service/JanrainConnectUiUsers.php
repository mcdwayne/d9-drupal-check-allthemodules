<?php

namespace Drupal\janrain_connect_ui\Service;

use Drupal\janrain_connect\Service\JanrainConnectConnector;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * JanrainConnect User Class.
 *
 * Responsible for integration with User module from Drupal Core. Creates the
 * User entity fields dynamically based on configuration and persists the Drupal
 * User.
 */
class JanrainConnectUiUsers {

  use StringTranslationTrait;

  /**
   * Janrain Connect Form Service.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService
   */
  private $janrainConnectFormService;

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
   * JanrainConnectUiFlowExtractorService.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService
   */
  protected $janrainConnectFlowExtractorService;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactory $config_factory,
    UserDataInterface $user_data,
    JanrainConnectUiFlowExtractorService $janrain_flow_extractor,
    LoggerChannelFactoryInterface $logger_factory,
    JanrainConnectConnector $janrain_connector,
    JanrainConnectUiFormService $janrain_connect_form_service
  ) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->userData = $user_data;
    $this->janrainConnectFlowExtractorService = $janrain_flow_extractor;
    $this->logger = $logger_factory->get('janrain_connect');
    $this->janrainConnector = $janrain_connector;
    $this->janrainConnectFormService = $janrain_connect_form_service;
  }

  /**
   * Function to Persist User.
   *
   * @param array $data
   *   User profile.
   * @param array $result
   *   Janrain data.
   * @param bool $return_user
   *   If user data should be returned.
   *
   * @return mixed
   *   User data or FALSE if not able to save.
   */
  public function persistUser(array $data, array $result, $return_user) {

    // Check mandatory fields.
    if (empty($result['capture_user']->uuid) || empty($data['emailAddress'])) {
      return FALSE;
    }

    $janrain_uuid = $result['capture_user']->uuid;

    $email = $data['emailAddress'];
    $password = user_password();

    $user = User::create();

    $user->setEmail($email);
    $user->setUsername($janrain_uuid);
    $user->setPassword($password);
    $user->enforceIsNew();
    $user->set('init', $email);
    $user->activate();

    // Set Janrain Role.
    $user->addRole('janrain');

    // Save user account.
    $status = $user->save();

    if (!empty($result['access_token'])) {
      $token = $result['access_token'];
      // Get Token in Session. @codingStandardsIgnoreLine
      $request = \Drupal::request();
      $session = $request->getSession();
      $session->set('janrain_connect_access_token', $token);
    }

    try {

      switch ($status) {

        case SAVED_NEW:

          // Todo: In login, implements the Janrain lib that already exists for
          // get user data from Janrain.
          $this->persistJanrainFieldsToDrupal($data, $user);

          break;

        case SAVED_UPDATED:
          $this->logger->warning($this->t('Unexpected scenario on create user. Details: Function return "SAVED_UPDATED"'));
          break;
      }

      if ($return_user) {
        return $user;
      }

      return $status;
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return FALSE;
    }
  }

  /**
   * Function to insert Janrain data to Drupal.
   */
  public function persistJanrainFieldsToDrupal($data, $user) {

    // Persists User to Drupal.
    $fields_persist_users = $this->config->get('fields_persist_users');

    if (!empty($fields_persist_users)) {

      $uid = $user->uid->value;

      // Get data field from yml (flow).
      $data_fields = $this->janrainConnectFlowExtractorService->getFieldsData();

      // Remove blank spaces for values.
      foreach ($fields_persist_users as $key => $field_persist_user) {

        // Replace because user not allow char ".".
        unset($fields_persist_users[$key]);
        $key = str_replace('@DOT@', '.', $key);
        $fields_persist_users[$key] = trim($field_persist_user);
      }

      foreach ($data as $field_key => $field_value) {

        if (empty($data_fields[$field_key]) || empty($data_fields[$field_key]['schemaId'])) {
          continue;
        }

        // Get data from field.
        $data_field = $data_fields[$field_key];

        // Get schema_id.
        $schema_id = $data_field['schemaId'];

        // Check if field should be persist.
        if (empty($fields_persist_users[$schema_id]) || $fields_persist_users[$schema_id] == '0') {
          continue;
        }

        $schema_id_persist = $fields_persist_users[$schema_id];

        // Persist data in Drupal.
        $this->userData->set('janrain_connect', $uid, $schema_id_persist, $field_value);
      }
    }
  }

  /**
   * Method to fill user values in Update Profile Form.
   */
  public function fillUserValuesUpdateProfileForm(&$form, $form_id) {

    // Get form data.
    $form_data = $this->janrainConnectFormService->getForm($form_id);

    if (empty($form_data) || empty($form_data['fields'])) {
      return FALSE;
    }

    $fields = $form_data['fields'];

    // Get Token in Session. @codingStandardsIgnoreLine
    $request = \Drupal::request();
    $session = $request->getSession();

    $token = $session->get('janrain_connect_access_token');

    if (empty($token)) {
      return FALSE;
    }

    $response = $this->janrainConnector->getUserData($token);

    if (empty($response) || empty($response['result'])) {
      return FALSE;
    }

    $result = $response['result'];

    foreach ($form as $field_key => $field) {

      if (empty($fields[$field_key]) || empty($fields[$field_key]['schema_id'])) {
        continue;
      }

      $schema_id = $fields[$field_key]['schema_id'];

      if (!empty($result[$schema_id])) {
        $form[$field_key]['#default_value'] = $result[$schema_id];
        continue;
      }

      if (strpos($schema_id, '.') !== FALSE) {

        $schema_data = explode('.', $schema_id);

        $default_value = $result;

        foreach ($schema_data as $schema_key) {
          if (is_array($default_value)) {
            $default_value = $default_value[$schema_key];
          }
          else {
            $default_value = $default_value->$schema_key;
          }

        }

        $form[$field_key]['#default_value'] = $default_value;
        continue;
      }
    }
  }

  /**
   * Method to get user by token.
   */
  public function getCurrentUser($token = FALSE, $force_logout = FALSE) {

    if (empty($token)) {

      // Make Request. @codingStandardsIgnoreLine
      $request = \Drupal::request();
      $session = $request->getSession();

      // Get Token in Session. @codingStandardsIgnoreLine
      $token = $session->get('janrain_connect_access_token');
    }

    if (empty($token)) {
      return FALSE;
    }

    $response = $this->janrainConnector->getUserData($token);

    if (empty($response) || empty($response['result'])) {

      if ($force_logout) {
        drupal_set_message($this->t('Session expired on Janrain'), 'warning');
        user_logout();

        $url = Url::fromRoute('<front>', [], []);
        $response = new RedirectResponse($url->toString());
        $response->send();
        return FALSE;
      }

      return FALSE;
    }

    return $response['result'];
  }

}
