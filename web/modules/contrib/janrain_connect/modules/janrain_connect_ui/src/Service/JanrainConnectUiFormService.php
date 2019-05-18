<?php

namespace Drupal\janrain_connect_ui\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use GuzzleHttp\ClientInterface;

/**
 * JanrainConnect Form Class.
 */
class JanrainConnectUiFormService {

  /**
   * JanrainConnect.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService
   */
  private $janrainConnectFlowExtractorService;

  /**
   * The HTTP client used to fetch remote definitions.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * JanrainConnectToken.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiTokenService
   */
  protected $janrainConnectUiTokenService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ClientInterface $http_client,
    JanrainConnectUiFlowExtractorService $janrain_connect_flow_extractor_service,
    ConfigFactory $config_factory,
    JanrainConnectUiTokenService $janrain_connect_ui_token_service
  ) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->httpClient = $http_client;
    $this->janrainConnectFlowExtractorService = $janrain_connect_flow_extractor_service;
    $this->janrainConnectUiTokenService = $janrain_connect_ui_token_service;
  }

  /**
   * Get Form.
   */
  public function getForm($form_id, $data_for_tests = FALSE) {

    $form = [];
    $id = $form_id;

    $form_data = $this->janrainConnectFlowExtractorService->getFormData($form_id, FALSE, $data_for_tests);

    if (empty($form_data)) {
      return FALSE;
    }

    if (!empty($form_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_ID])) {
      $id = $form_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_ID];
    }

    $form['id'] = $id;
    $field_key_mapping = $this->config->get('field_key_mapping');

    foreach ($form_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELD_DATA] as $key => $field_data) {

      // Todo: Get mapping classes.
      $field_id = $key;
      $type = $label = FALSE;
      $key_options = 'options';
      $validations = [];
      $options = [];
      $classes = [];
      $placeholder = $tip = '';

      if (!empty($field_data['element']) && $field_data['element'] == 'select') {
        $field_data['type'] = $field_data['element'];
      }

      // Check if field use default or custom key for 'options'.
      if (!empty($field_key_mapping[$key][$key_options])) {
        $key_options = $field_key_mapping[$key][$key_options];
      }

      if (!empty($field_data['type'])) {
        $type = $field_data['type'];
      }

      if (!empty($field_data['label'])) {
        $label = $this->janrainConnectUiTokenService->replace($field_data['label']);
      }

      if (!empty($field_data['placeholder'])) {
        $placeholder = $field_data['placeholder'];
      }

      if (!empty($field_data['tip'])) {
        $tip = $field_data['tip'];
      }

      if (!empty($field_data['schemaId'])) {
        $schema_id = $field_data['schemaId'];
      }

      if (!empty($field_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_VALIDATION])) {
        $validations = $field_data[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_VALIDATION];
      }

      if ($key == 'birthdate') {

        $field_type_class_mapping = $this->config->get('field_type_class_mapping');

        if ($field_type_class_mapping[$type]) {
          $classes[] = $field_type_class_mapping[$type];
        }
      }

      if (!empty($field_data[$key_options])) {

        $janrain_connect_options = $field_data[$key_options];

        $options = [];

        foreach ($janrain_connect_options as $key_option => $janrain_connect_option) {

          if (is_string($janrain_connect_option)) {
            $options[$key_option] = $janrain_connect_option;
            continue;
          }

          $key = $janrain_connect_option['value'];
          $text = $janrain_connect_option['text'];

          $options[$key] = $text;
        }
      }

      $form['fields'][$field_id] = [
        'type' => $type,
        'label' => $label,
        'validations' => $validations,
        'options' => $options,
        'classes' => $classes,
        'placeholder' => $placeholder,
        'description' => $tip,
        'schema_id' => $schema_id,
      ];
    }

    return $form;
  }

}
