<?php

namespace Drupal\global_gateway;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\SessionManager;

/**
 * Provides a helper functions for module.
 */
class Helper {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Drupal\Core\Session\SessionManager definition.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $sessionManager;

  /**
   * User data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Disabled regions processor.
   *
   * @var \Drupal\global_gateway\DisabledRegionsProcessor
   */
  protected $disabledRegionsProcessor;

  /**
   * GlobalGatewaySwitcherForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\user\PrivateTempStoreFactory $user_private_tempstore
   *   User private temp store.
   * @param \Drupal\Core\Session\SessionManager $session_manager
   *   The session manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current active user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\global_gateway\DisabledRegionsProcessor $disabled_processor
   *   Disabled regions processor.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    PrivateTempStoreFactory $user_private_tempstore,
    SessionManager $session_manager,
    UserDataInterface $user_data,
    AccountInterface $current_user,
    RequestStack $request_stack,
    DisabledRegionsProcessor $disabled_processor
  ) {
    $this->moduleHandler            = $module_handler;
    $this->sessionManager           = $session_manager;
    $this->tempStore                = $user_private_tempstore->get('global_gateway');
    $this->userData                 = $user_data;
    $this->currentUser              = $current_user;
    $this->request                  = $request_stack->getCurrentRequest();
    $this->disabledRegionsProcessor = $disabled_processor;
  }

  /**
   * Get region code of the current user.
   *
   * @return string|bool
   *   Region code of current user. FALSE otherwise.
   */
  public function fetchCurrentRegionCode() {
    // If user is Authenticated, then get value from the specified field.
    // else try to detect using region detection module.
    $region_code = FALSE;
    $uid = $this->currentUser->id();

    if ($this->currentUser->isAuthenticated()) {
      $region_code = $this->userData->get('global_gateway', $uid, 'current_region');
      if (empty($region_code)) {
        $region_code = RegionDetector::detectRegionCode();
      }
    }
    // If user is Anonymous get region code from session.
    // or from region detection module.
    else {
      $region_code = $this->tempStore->get('current_region');

      if (empty($region_code)) {
        $region_code = RegionDetector::detectRegionCode();
      }

      if (!is_null($region_code) && $this->disabledRegionsProcessor->isDisabled($region_code)) {
        $region_code = $this->disabledRegionsProcessor
          ->getFallbackRegionCode($region_code);
      }
    }
    return $region_code;
  }

  /**
   * Set region code for the current user.
   *
   * @param string $code
   *   Region code which need to save.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function saveRegionCode($code) {
    if ($this->currentUser->isAuthenticated()) {
      $this->userData->set('global_gateway', $this->currentUser->id(), 'current_region', $code);
    }
    else {
      if (!isset($_SESSION['session_started'])) {
        $_SESSION['session_started'] = TRUE;
        $this->sessionManager->start();
      }
      $this->tempStore->set('current_region', $code);
    }
  }

  /**
   * Build options attributes for select_icons.
   *
   * @param array $regions
   *   Build only attributes for specified regions.
   *
   * @return array
   *   A list of mappings: region_code and classes for it.
   */
  public static function getOptionAttributes(array $regions = []) {
    if (empty($regions)) {
      $regions = \Drupal::service('country_manager')->getList();
    }

    $mapper = \Drupal::service('flags.mapping.country');
    return $mapper->getOptionAttributes(
      array_keys($regions)
    );

  }

  /**
   * Build region select list options.
   *
   * @param array $region_code
   *   Array of region codes.
   *
   * @return array
   *   A list of region codes and labels, plus empty option.
   */
  public static function getRegionsList(array $region_code = []) {
    $empty = ['none' => t('- Select region -')];

    $region_list = \Drupal::service('country_manager')->getList();

    self::removeDisabledRegions($region_list);

    foreach ($region_code as $code) {
      $regions[$code] = $region_list[$code];
    }
    if (empty($regions)) {
      $regions = $region_list;
    }
    return $empty + $regions;
  }

  /**
   * Check if soft dependencies are meet.
   */
  public static function softDependenciesMeet() {
    return \Drupal::moduleHandler()->moduleExists('select_icons')
    && \Drupal::moduleHandler()->moduleExists('flags');
  }

  /**
   * Remove disabled regions from the list.
   *
   * @param array &$regions
   *   Regions list array to be filtered out.
   */
  private static function removeDisabledRegions(&$regions) {
    $disabled = \Drupal::config('global_gateway.disabled_regions')
      ->get('disabled');
    foreach ($regions as $region_code => $region) {
      if (in_array(strtolower($region_code), $disabled)) {
        unset($regions[$region_code]);
      }
    }
  }

}
