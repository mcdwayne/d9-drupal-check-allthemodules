<?php

namespace Drupal\popup_maker\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings Controller to the popup maker module.
 */
class SettingsController extends ControllerBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Popup Maker's website URL.
   *
   * @var string $popupMakerServiceURL
   */
  const POPUP_MAKER_SERVICE_URL = 'https://popupmaker.com/';

  /**
   * Popup Maker controller constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Stores runtime messages.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   Provides an interface for entity type managers.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   Returns the CSRF token manager service.
   * @param \GuzzleHttp\Client $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The Braintree Cashier logger channel.
   */
  public function __construct(MessengerInterface $messenger, ConfigFactory $configFactory, EntityManager $entityManager, EntityTypeManager $entityTypeManager, CsrfTokenGenerator $csrfTokenGenerator, Client $httpClient, LoggerChannelFactory $logger) {
    $this->messenger = $messenger;
    $this->configFactory = $configFactory;
    $this->entityManager = $entityManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->csrfToken = $csrfTokenGenerator;
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('csrf_token'),
      $container->get('http_client'),
      $container->get('logger.factory')
    );
  }

  /**
   * Editing popup settings.
   */
  public function editPopupSettings() {
    $config = $this->configFactory->get('popup_maker.settings');

    $settings = [
      'api_key' => $config->get('api_key'),
      'popups' => $config->get('popups'),
      'user' => $config->get('user'),
      'popupSettings' => $config->get('popupSettings'),
    ];

    $contentTypes = $this->entityManager->getStorage('node_type')->loadMultiple();

    $displayRules = [];
    if (!empty($contentTypes)) {
      foreach ($contentTypes as $key => $contentType) {
        $displayRules[$key] = [];
        $nodes = $this->entityTypeManager
          ->getListBuilder('node')
          ->getStorage()
          ->loadByProperties(['type' => $key]);
        foreach ($nodes as $node) {
          $displayRules[$key][$node->id()] = $node->getTitle();
        }
      }
    }

    return [
      '#theme' => 'popup_maker_settings',
      '#settings' => $settings,
      '#edit' => TRUE,
      '#editingPopupId' => (int) $_GET['id'],
      '#displayRules' => $displayRules,
      '#token' => $this->csrfToken->get('popup_maker_api_key'),
      '#attached' => [
        'library' => [
          'popup_maker/popup_maker_admin',
        ],
      ],
    ];
  }

  /**
   * Functio to show the popup maker.
   */
  public function show() {
    $config = $this->configFactory->get('popup_maker.settings');

    $settings = [
      'api_key' => $config->get('api_key'),
      'popups' => $config->get('popups'),
      'user' => $config->get('user'),
      'popupSettings' => $config->get('popupSettings'),
    ];

    return [
      '#theme' => 'popup_maker_settings',
      '#settings' => $settings,
      '#token' => $this->csrfToken->get('popup_maker_api_key'),
      '#attached' => [
        'library' => [
          'popup_maker/popup_maker_admin',
        ],
      ],
    ];
  }

  /**
   * To update the popup.
   */
  public function updatePopup() {
    $id = $_POST['id'];

    $config = $this->configFactory->getEditable('popup_maker.settings');

    $popupSettings = $config->get('popupSettings');

    $popupSettings[$id]['displayOptions'] = $_POST['sgpm_rules'];

    $config->set('popupSettings', $popupSettings)->save();

    return $this->redirect('popup_maker.settings.edit_popup_settings', ['id' => $id]);
  }

  /**
   * To refresh the popups.
   */
  public function refreshPopups() {
    $config = $this->configFactory->get('popup_maker.settings');

    if ($this->refreshData($config->get('api_key'))) {
      $this->messenger->addStatus('Popups list refreshed successfully');
    }

    return $this->redirect('popup_maker.settings');
  }

  /**
   * To ypdate the API key.
   */
  public function updateApiKey() {
    if (!isset($_POST['sgpm-api-key-submit'])) {
      $this->messenger->addError('Invalid request');
      return FALSE;
    }

    if (!$this->csrfToken->validate($_POST['_csrf_token'], 'popup_maker_api_key')) {
      $this->messenger->addError('Could not validate the security token');
      return FALSE;
    }

    if ($this->refreshData($_POST['sgpm-api-key'])) {
      $this->configFactory
        ->getEditable('popup_maker.settings')
        ->set('api_key', $_POST['sgpm-api-key'])
        ->save();

      $this->messenger->addStatus('New api key saved successfully');
    }

    return $this->redirect('popup_maker.settings');
  }

  /**
   * To update the status of popup.
   */
  public function updateStatus() {
    if (isset($_POST['sgpm-disable-popup'])) {
      $this->disablePopup();
    }

    if (isset($_POST['sgpm-enable-popup'])) {
      $this->enablePopup();
    }

    return $this->redirect('popup_maker.settings');

  }

  /**
   * To refresh the data of popup.
   */
  public function refreshData($apiKey) {
    if (empty($apiKey)) {
      $this->messenger->addError('Api Key is required to connect to the service');
      return FALSE;
    }

    $data = [
      'apiKey' => $apiKey,
      'appname' => 'Drupal',
    ];

    $client = $this->httpClient;

    try {
      $request = $client->post(SettingsController::POPUP_MAKER_SERVICE_URL . 'app/connect', [
        'form_params' => $data,
      ]);
      $data = json_decode($request->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->get('popup_maker')->error($e);
      return FALSE;
    }

    $config = $this->configFactory->getEditable('popup_maker.settings');

    if (!isset($data['isAuthenticate']) || !$data['isAuthenticate']) {
      $this->messenger->addError('Please, provide a valid Api Key');
      return FALSE;
    }

    $config->set('popups', array_reverse($data['popups'], TRUE))
      ->set('user', $data['user'])
      ->save();

    $popupSettings = $config->get('popupSettings');

    if (empty($popupSettings)) {
      $popupSettings = [];
    }

    foreach ($data['popups'] as $popupId => $popup) {
      if (!isset($popupSettings[$popupId])) {
        $popupSettings[$popupId] = [
          'enabled' => 0,
          'displayOptions' => [],
        ];
      }
    }

    $config->set('popupSettings', $popupSettings)->save();

    $this->messenger->addStatus('Data imported from service successfully');
    return TRUE;
  }

  /**
   * To disable the popup.
   */
  public function disablePopup() {
    if (!isset($_POST['id'])) {
      $this->messenger->addError('Parameter "id" is required to disable a popup');
      return FALSE;
    }

    $id = abs(intval($_POST['id']));

    if ($id != $_POST['id']) {
      $this->messenger->addError('Parameter "id" must be non negative integer');
      return FALSE;
    }

    $config = $this->configFactory->getEditable('popup_maker.settings');

    $popupSettings = $config->get('popupSettings');

    if (empty($popupSettings)) {
      $popupSettings = [];
    }

    if (!isset($popupSettings[$_POST['id']])) {
      $popupSettings[$_POST['id']] = [];
    }

    $popupSettings[$_POST['id']]['enabled'] = 0;

    $config->set('popupSettings', $popupSettings)->save();

    $this->messenger->addStatus('Popup Disabled successfully');
  }

  /**
   * To enable the popup.
   */
  public function enablePopup() {
    if (!isset($_POST['id'])) {
      $this->messenger->addError('Parameter "id" is required to disable a popup');
      return FALSE;
    }

    $id = abs(intval($_POST['id']));

    if ($id != $_POST['id']) {
      $this->messenger->addError('Parameter "id" must be non negative integer');
      return FALSE;
    }

    $config = $this->configFactory->getEditable('popup_maker.settings');

    $popupSettings = $config->get('popupSettings');

    if (empty($popupSettings)) {
      $popupSettings = [];
    }

    if (!isset($popupSettings[$_POST['id']])) {
      $popupSettings[$_POST['id']] = [];
    }

    $popupSettings[$_POST['id']]['enabled'] = 1;

    $config->set('popupSettings', $popupSettings)->save();

    $this->messenger->addStatus('Popup Enabled successfully');
  }

}
