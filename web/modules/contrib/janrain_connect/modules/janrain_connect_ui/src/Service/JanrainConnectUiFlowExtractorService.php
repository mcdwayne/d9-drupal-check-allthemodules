<?php

namespace Drupal\janrain_connect_ui\Service;

use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JanrainConnect Class.
 */
class JanrainConnectUiFlowExtractorService {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    ConfigFactory $config_factory,
    ContainerInterface $container
  ) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->languageManager = $language_manager;
    $this->container = $container;
  }

  /**
   * Function to get a Form Data.
   */
  public function getFormData($form_id, $full_response = FALSE, $data_for_tests = FALSE) {

    $form_data = [];
    $fields = [];
    $field_data = FALSE;
    $action = FALSE;
    $next = FALSE;

    $flow_data = $this->getYamlParsedToArray($data_for_tests);

    if (empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id])) {
      return FALSE;
    }

    if (!empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_AUTH_FIELDS])) {
      $auth_fields = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_AUTH_FIELDS];
    }

    if (!empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS])) {

      $fields = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS];

      if (!empty($auth_fields)) {
        $fields = array_merge($auth_fields, $fields);
      }
    }

    if (!empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ACTION])) {
      $action = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ACTION];
    }

    if (!empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_NEXT])) {
      $next = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$form_id][JanrainConnectWebServiceConstants::JANRAIN_CONNECT_NEXT];
    }

    $field_data = [];
    foreach ($fields as $field) {
      if (!empty($flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$field])) {
        $field_data[$field] = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS][$field];
      }
    }

    $form_data = [
      'form_id' => $form_id,
      'fields' => $fields,
      'field_data' => $field_data,
    ];

    if ($full_response) {
      $form_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ACTION] = $action;
      $form_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_NEXT] = $next;
    }

    return $form_data;
  }

  /**
   * Function to get schema key of fields.
   */
  public function getFieldsSchemaKey() {

    // Default values.
    $fields_schema_key = [];
    $schema_id = '';

    $fields = $this->getFieldsData();

    if (!$fields) {
      return FALSE;
    }

    foreach ($fields as $field) {

      if (empty($field['schemaId']) || !is_string($field['schemaId'])) {
        continue;
      }

      $schema_id = $field['schemaId'];

      // Replace because user not allow char ".".
      $key_schema_id = str_replace('.', '@DOT@', $schema_id);

      $fields_schema_key[$key_schema_id] = $schema_id;
    }

    return $fields_schema_key;
  }

  /**
   * Function to get all Fields data.
   */
  public function getFieldsData() {

    $flow_data = $this->getYamlParsedToArray();

    $fields = $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS];

    if (!is_array($fields)) {
      return FALSE;
    }

    // Clear forms to fields.
    foreach ($fields as $field_key => $field) {

      $is_form = strpos($field_key, 'Form');

      if ($is_form !== FALSE) {
        unset($fields[$field_key]);
      }
    }

    if (empty($fields)) {
      return FALSE;
    }

    return $fields;
  }

  /**
   * Function to get all Forms data.
   */
  public function getFlowVersion() {

    $flow_data = $this->getYamlParsedToArray();

    if (!$flow_data || !isset($flow_data['version'])) {
      return FALSE;
    }

    return $flow_data['version'];

  }

  /**
   * Function to get all Forms data.
   */
  public function getFormsData() {

    $flow_data = $this->getYamlParsedToArray();

    $forms = array_filter(
      $flow_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELDS],
      function ($key) {
        return (strpos($key, 'Form'));
      },
      ARRAY_FILTER_USE_KEY
    );

    if (empty($forms)) {
      return FALSE;
    }

    return $forms;
  }

  /**
   * Undocumented function.
   */
  private function getYamlParsedToArray($data_for_tests = FALSE) {

    if ($data_for_tests) {
      $yml_path = 'janrain/tests';

      // Get yml for tests.
      $yml_name = 'janrain_flow.yml';

      $yml = DRUPAL_ROOT . '/' . drupal_get_path('module', 'janrain_connect') . '/tests/flow_mock/' . $yml_name;

    }
    else {

      $yml_path = $this->config->get('yml_path');

      // Get default yml.
      $yml_name = 'janrain_connect_flow_default.yml';
      $path_yml_default = 'public://' . $yml_path . '/' . $yml_name;
      $yml = $this->container->get('file_system')->realpath($path_yml_default);

      // Get yml by language.
      $language = $this->languageManager->getCurrentLanguage();
      $lid = $language->getId();

      $yml_language = 'janrain_connect_flow_' . $lid . '.yml';
      $yml = 'public://' . $yml_path . '/' . $yml_language;
      $yml = $this->container->get('file_system')->realpath($yml);
    }

    $yml = strtolower($yml);

    $yml = str_replace('-', '_', $yml);

    if (!file_exists($yml)) {
      return FALSE;
    }

    $yml_file = file_get_contents($yml);

    if (!$yml_file) {
      return FALSE;
    }

    $flow_data = Yaml::parse($yml_file);

    if (!$flow_data) {
      return FALSE;
    }

    return $flow_data;
  }

}
