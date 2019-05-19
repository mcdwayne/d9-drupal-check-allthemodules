<?php

namespace Drupal\webform_prepopulate;

use Drupal\Core\TempStore\TempStoreException;
use Drupal\Component\Utility\Xss;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Entity\Webform;

/**
 * Class WebformPrepopulateUtils.
 */
class WebformPrepopulateUtils {

  const MAX_HASH_ACCESS = 5;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempstorePrivate;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\webform_prepopulate\WebformPrepopulateStorage definition.
   *
   * @var \Drupal\webform_prepopulate\WebformPrepopulateStorage
   */
  protected $webformPrepopulateStorage;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new WebformPrepopulateUtils object.
   */
  public function __construct(
    PrivateTempStoreFactory $tempstore_private,
    ConfigFactoryInterface $config_factory,
    WebformPrepopulateStorage $webform_prepopulate_storage,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->tempstorePrivate = $tempstore_private;
    $this->configFactory = $config_factory;
    $this->webformPrepopulateStorage = $webform_prepopulate_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Checks the amount of hash access for a Webform within a session.
   *
   * @param string $hash
   * @param string $webform_id
   *
   * @return bool
   */
  public function hasHashAccess($hash, $webform_id) {
    // Bypass by site wide permission.
    if ($this->currentUser->hasPermission('bypass webform prepopulate hash access limit')) {
      return TRUE;
    }

    // Bypass by Webform configuration.
    if ($this->getWebformSetting('disable_hash_access_limit', $webform_id) === 1) {
      return TRUE;
    }

    // Exclude bots, as they will use several sessions,
    // the tempStore is not useful here.
    $userAgent = Xss::filter($this->requestStack->getCurrentRequest()->headers->get('user-agent'));
    if (
      empty($userAgent) ||
      // @todo review bots list
      (!empty($userAgent) && preg_match('~(bot|crawl|python)~i', $userAgent))
    ) {
      $this->loggerFactory->get('webform_prepopulate')->warning('Bot access blocked for user agent @agent.', [
        '@agent' => $userAgent,
      ]);
      return FALSE;
    }

    // Main case for a single session.
    $tempStore = $this->tempstorePrivate->get('webform_prepopulate');
    $accessedHashes = [];
    try {
      if (empty($tempStore->get('accessed_hashes_' . $webform_id))) {
        $accessedHashes[] = $hash;
        $tempStore->set('accessed_hashes_' . $webform_id, $accessedHashes);
      }
      else {
        $accessedHashes = $tempStore->get('accessed_hashes_' . $webform_id);
        if (!in_array($hash, $accessedHashes)) {
          $accessedHashes[] = $hash;
          $tempStore->set('accessed_hashes_' . $webform_id, $accessedHashes);
        }
      }
    }
    catch (TempStoreException $exception) {
      $this->loggerFactory->get('webform_prepopulate')->warning($exception->getMessage());
    }

    $result = count($accessedHashes) <= self::MAX_HASH_ACCESS;

    if (!$result) {
      $this->loggerFactory->get('webform_prepopulate')->warning(t('Hash access limit reached for ip @ip.', [
        '@ip' => $this->requestStack->getCurrentRequest()->getClientIp(),
      ]));
    }

    return $result;
  }

  /**
   * Returns an array of all the Webform entities.
   *
   * @return array|\Drupal\webform\Entity\Webform[]
   */
  public function getWebformEntities() {
    $result = [];
    try {
      $result = $this->entityTypeManager->getStorage('webform')->loadMultiple();
    }
    catch (\Throwable $exception) {
      $this->loggerFactory->get('webform_prepopulate')->error($exception->getMessage());
    }
    return $result;
  }

  /**
   * Checks if prepopulate from a file data source is enabled.
   *
   * @param string $webform_id
   *
   * @return bool
   */
  public function isFilePrepopulateEnabled($webform_id) {
    return $this->getWebformSetting('form_prepopulate_enable_file', $webform_id) === 1;
  }

  /**
   * Checks if the Webform prepopulate data must be removed when it is closed.
   *
   * @param string $webform_id
   *
   * @return bool
   */
  public function deleteDataOnClose($webform_id) {
    return $this->getWebformSetting('delete_data_on_webform_close', $webform_id) === 1;
  }

  /**
   * Get setting defined via the hook_form_webform_settings_form_form_alter().
   *
   * @param string $setting
   * @param string $webform_id
   *
   * @return mixed
   */
  public function getWebformSetting($setting, $webform_id) {
    $result = NULL;
    $webformEntity = Webform::load($webform_id);
    if (
      !empty($webformEntity) &&
      !empty($settings = $webformEntity->getThirdPartySettings('webform_prepopulate')) &&
      isset($settings[$setting])
    ) {
      $result = $settings[$setting];
    }
    return $result;
  }

}
