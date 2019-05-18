<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService;
use GuzzleHttp\Client;
use JanrainRest\JanrainRest as Janrain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Form for configure messages.
 */
class JanrainConnectUiSyncForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * JanrainConnectFlowExtractorService.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService
   */
  private $janrainConnectUiFlowExtractorService;

  /**
   * ClientInterface.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  const REQUEST_ERROR_MESSAGE = "<b>%s:</b> Flow not found for this locale. Error: %s";
  const REQUEST_SUCCESS_MESSAGE = "<b>%s:</b> Status OK";

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    JanrainConnectUiFlowExtractorService $janrain_connect_ui_flow_extractor_service,
    Client $client,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->languageManager = $language_manager;
    $this->janrainConnectUiFlowExtractorService = $janrain_connect_ui_flow_extractor_service;
    $this->client = $client;
    $this->loggerFactory = $logger_factory->get('janrain_connect_drupal8');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('language_manager'),
        $container->get('janrain_connect_ui.flow_extractor'),
        $container->get('http_client'),
        $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_sync';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');

    $yml_path = $config->get('yml_path');

    $application_id = $config->get('application_id');
    $flowjs_url = $config->get('flowjs_url');
    $flow_version = $config->get('flow_version');

    $yml_flow_version = $this->janrainConnectUiFlowExtractorService->getFlowVersion();

    // Check configurations.
    if (empty($application_id) || empty($flowjs_url)) {

      drupal_set_message($this->t('Before executing the sync please fill in the Janrain settings.'), 'error');

      $url_settings = Url::fromRoute('janrain_connect.settings')->toString();

      return new RedirectResponse($url_settings);
    }

    $links_flow = '';

    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $language) {

      $language_name = $language->getName();

      $lid = $language->getId();

      $lang_code = $this->getFlowLanguageByLid($lid);

      try {
        $flow = $this->getFlow($flow_version, $lang_code);
      }
      catch (\Exception $e) {
        $status_code = $e->getCode();
        $this->loggerFactory->error($e->getMessage());
      }

      if (empty($flow)) {
        $links_flow = sprintf(self::REQUEST_ERROR_MESSAGE, $language_name, $status_code);
        continue;
      }

      $links_flow = sprintf(self::REQUEST_SUCCESS_MESSAGE, $language_name);
    }

    $form['yml_path'] = [
      '#type' => 'details',
      '#title' => $this->t("Yml path"),
      '#open' => TRUE,
    ];

    $form['yml_path']['yml_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Janrain directory'),
      '#description' => $this->t('A local file system path where configuration yaml files will be stored.'),
      '#field_prefix' => file_build_uri(''),
      '#default_value' => $yml_path,
    ];

    $form['flow_js_links'] = [
      '#type' => 'details',
      '#title' => $this->t("URL(s) Flow"),
      '#open' => TRUE,
    ];

    if ($flow_version === $yml_flow_version) {
      drupal_set_message($this->t('The configured flow version is already synchronized'));
    }
    else {
      drupal_set_message($this->t('The configured flow version is not yet synchronized'), 'warning');
    }

    $form['flow_js_links']['flow_sync_version'] = [
      '#type' => 'item',
      '#title' => $this->t('Synchronized Flow Version'),
      '#markup' => $yml_flow_version,
    ];

    $form['flow_js_links']['flow_urls'] = [
      '#type' => 'markup',
      '#markup' => $links_flow,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $sync_completed = FALSE;
    $exported_files = [];

    $yml_path = $form_state->getValue('yml_directory');

    $config = $this->config('janrain_connect.settings');
    $config->set('yml_path', $yml_path);
    $config->save();

    $default_language = $config->get('default_language');
    $flow_version = $config->get('flow_version');

    $languages = $this->languageManager->getLanguages();

    try {
      $flow = $this->getFlow($flow_version, $default_language);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e->getMessage());
    }

    $flow = Yaml::parse(json_encode($flow));
    $flow = Yaml::dump($flow, 10, 2);

    // Generate Default Yaml.
    $file_exported = $this->exportYmlFile($flow, $yml_path);

    if ($file_exported) {
      $exported_files[] = $file_exported;
    }

    // Generate Yaml by language.
    foreach ($languages as $language) {

      $lid = $language->getId();

      $lang_code = $this->getFlowLanguageByLid($lid);

      try {
        $flow = $this->getFlow($flow_version, $lang_code);
      }
      catch (\Exception $e) {
        $this->loggerFactory->error($e->getMessage());
        continue;
      }

      $flow = Yaml::parse(json_encode($flow));
      $flow = Yaml::dump($flow, 10, 2);

      $file_exported = $this->exportYmlFile($flow, $yml_path, $lid);

      if ($file_exported) {

        $exported_files[] = $file_exported;

        if (!$sync_completed) {
          $sync_completed = TRUE;
        }
      }
    }

    if ($sync_completed) {

      drupal_set_message($this->t('Sync completed'), 'status');

      if (!empty($exported_files)) {
        drupal_set_message($this->t('Exported yaml files:'), 'status');
      }

      foreach ($exported_files as $exported_file) {
        drupal_set_message($exported_file, 'status');
      }
    }
  }

  /**
   * Function to get Flow Language By Lid.
   */
  private function getFlowLanguageByLid($lid) {

    $config = $this->config('janrain_connect.settings');

    $lang_code = $config->get('default_language');

    $flow_language_key = 'flow_language_mapping_' . $lid;

    if (!empty($config->get($flow_language_key))) {
      $lang_code = $config->get($flow_language_key);
    }

    return $lang_code;
  }

  /**
   * Function to export Yaml File.
   *
   * Write the YML to a physical file.
   *
   * @param string $flow_yaml
   *   The YML to be written to disk.
   * @param string $yml_path
   *   The relative path of the YML file.
   * @param mixed $lang_code
   *   If available, lang_code is appended to the file name.
   *
   * @return string
   *   Absolute path of YML written YML file.
   */
  private function exportYmlFile(string $flow_yaml, string $yml_path, $lang_code = FALSE) {
    if (!$flow_yaml) {
      drupal_set_message($this->t('Error on export file'), 'error');
    }

    $file_name = 'janrain_connect_flow_default.yml';

    if ($lang_code) {

      $lang_code = strtolower($lang_code);
      $lang_code = str_replace('-', '_', $lang_code);

      $file_name = 'janrain_connect_flow_' . $lang_code . '.yml';
    }

    $public_path = PublicStream::basePath();
    $path = drupal_realpath($public_path);
    $yml_path = $public_path . '/' . $yml_path;

    if (file_prepare_directory($yml_path, FILE_CREATE_DIRECTORY)) {
      $path = $yml_path;
    }

    $saved_file = file_put_contents($path . '/' . $file_name, $flow_yaml);

    try {
      chmod($path . '/' . $file_name, 0664);
    }
    catch (Exception $e) {
      throw new Exception('Unable to change file mode');
    }

    if (!$saved_file) {
      drupal_set_message($this->t('Error on export file'), 'error');
      return FALSE;
    }

    return $path . '/' . $file_name;
  }

  /**
   * Get Flow from Janrain.
   *
   * @param string $flow_version
   *   The flow version.
   * @param string $lang_code
   *   The locale code.
   *
   * @return object
   *   Flow content.
   */
  private function getFlow(string $flow_version, string $lang_code) {
    $config = $this->config('janrain_connect.settings');

    $janrainApi = new Janrain(
      $config->get('capture_server_url'),
      $config->get('config_server'),
      $config->get('flowjs_url'),
      '',
      '',
      $config->get('client_id'),
      $config->get('client_secret'),
      $config->get('application_id'),
      $config->get('default_language'),
      $config->get('flow_name'),
      $this->loggerFactory
    );

    return $janrainApi->getFlow($flow_version, $lang_code);
  }

}
