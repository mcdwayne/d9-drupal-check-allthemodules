<?php

namespace Drupal\warden\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\warden\Service\WardenManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default controller for the warden module.
 */
class DefaultController extends ControllerBase {

  /**
   * Warden manager service.
   *
   * @var WardenManager
   */
  protected $wardenManager;

  /**
   * @var Config
   */
  protected $wardenConfig;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var WardenManager $warden_manager */
    $warden_manager = $container->get('warden.manager');

    /** @var ConfigFactory $config_factory */
    $config_factory = $container->get('config.factory');

    $warden_settings = $config_factory->get('warden.settings');
    return new static($warden_manager, $warden_settings);
  }

  /**
   * @param WardenManager $wardenManager
   *   The Warden Manager service
   * @param Config $wardenConfig
   *   The config where all the Warden settings are storied.
   */
  public function __construct(WardenManager $wardenManager, Config $wardenConfig) {
    $this->wardenManager = $wardenManager;
    $this->wardenConfig = $wardenConfig;
  }

  /**
   * @return \Drupal\Core\Config\Config
   */
  public function getWardenConfig() {
    return $this->wardenConfig;
  }

  /**
   * @return WardenManager
   */
  public function getWardenManager() {
    return $this->wardenManager;
  }

  /**
   * Warden registration form.
   *
   * @return array
   */
  public function wardenRegistration() {
    $build = [];

    $warden_path = $this->getWardenConfig()->get('warden_server_host_path');

    if (empty($warden_path)) {
      drupal_set_message(t('You are missing some Warden configuration. Please read the README file for more details.'), 'error');
      return [];
    }

    $build['local_token'] = [
      '#markup' => '<p>' . t('Site security token: %token', [
          '%token' => $this->getWardenManager()->getLocalToken(),
        ]) . '</p>'
    ];

    $build['message'] = [
      '#markup' => '<p>' . t('To add this site to your Warden dashboard click the button below. Read the README file which comes with the module for configuration information.') . '</p>'
    ];

    $build['button'] = [
      '#markup' => t('<a class="button" href="@url">Add this site to your Warden Dashboard</a>', array(
        '@url' => $this->generateWardenRegistrationRedirect(),
      )),
    ];

    return $build;
  }

  /**
   * Generate a redirect to the Warden server for site registration.
   *
   * @return string
   * @throws \Exception
   * @throws \WardenApi\Exception\EncryptionException
   */
  protected function generateWardenRegistrationRedirect() {
    global $base_url;
    $site_url = $base_url;
    $site_url .= "|" . $this->getWardenManager()->getLocalToken();
    $site_url_encrypted = $this->getWardenManager()->encrypt($site_url);
    $site_host_path = $this->getWardenConfig()->get('warden_server_host_path');
    return $site_host_path . '/sites/add?data=' . $site_url_encrypted;
  }

  /**
   * Access control to ensure authorised requests to get system data.
   *
   * @return AccessResult
   * @throws \Exception
   */
  public function wardenAccess() {
    $allow_requests = $this->getWardenConfig()->get('warden_allow_requests');

    if (empty($allow_requests)) {
      \Drupal::logger('warden')->warning('Update request denied: warden_allow_requests is set to FALSE', []);
      return AccessResult::forbidden();
    }

    if (empty($_POST) || empty($_POST['token'])) {
      \Drupal::logger('warden')->warning('Update request denied: request body is empty or missing the security token', []);
      return AccessResult::forbidden();
    }

    if (!$this->getWardenManager()->isValidWardenToken($_POST['token'], REQUEST_TIME)) {
      \Drupal::logger('warden')->warning('Update request denied: Failed to validate security token in request at timestamp @time', [
        '@time' => REQUEST_TIME
        ]);
      return AccessResult::forbidden();
    }

    $allowed_ips = $this->getWardenConfig()->get('warden_public_allow_ips');

    if (!empty($allowed_ips)) {
      $ip_address = \Drupal::request()->getClientIp();
      $allowed_ips = explode(',', $this->getWardenConfig()->get('warden_public_allow_ips'));

      foreach ($allowed_ips as &$address) {
        if ($ip_address === $address) {
          return AccessResult::allowed();
        }
      }

      // No IP addresses match.
      \Drupal::logger('warden')->warning('Update request denied: The requesting IP is not in the warden_public_allow_ips whitelist - @ip', [
        '@ip' => $ip_address
        ]);
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

  /**
   * Routing callback to retrieve the data stored on the site.
   *
   * @return JsonResponse
   */
  public function wardenStatus() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $response = new JsonResponse();

    try {
      $this->getWardenManager()->updateWarden();
      $response->setData(['data' => 'OK']);
    }
    catch (\Exception $e) {
      watchdog_exception('warden', $e);
      $response->setStatusCode(500);
      $response->setData(['error' => 'Internal fault']);
    }

    return $response;
  }

}
