<?php

namespace Drupal\janrain_connect_admin_services\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\janrain_connect_admin_services\Service\JanrainConnectAdminServicesCalls;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JanrainConnectAdminServicesAccountController.
 *
 * @package Drupal\janrain_connect_social\Controller
 */
class JanrainConnectAdminServicesAccountController extends ControllerBase {

  /**
   * Janrain admin service calls service.
   *
   * @var \Drupal\janrain_connect_admin_services\Service\JanrainConnectAdminServicesCalls
   */
  private $janrainConnectAdminServicesCallsService;

  /**
   * {@inheritdoc}
   */
  public function __construct(JanrainConnectAdminServicesCalls $janrain_connect_admin_services_calls) {
    $this->janrainConnectAdminServicesCallsService = $janrain_connect_admin_services_calls;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('janrain_connect_admin_services.calls')
    );
  }

  /**
   * Delete Account on Janrain.
   */
  public function deleteAccount() {
    $redirect = '<front>';
    $config = $this->config('janrain_connect_admin_services.settings');

    $result = $this
      ->janrainConnectAdminServicesCallsService
      ->entityDelete($this->currentUser()->getAccountName());

    if (empty($result['has_errors'])) {
      user_delete($this->currentUser()->id());
      $destination = $this->getDestinationByPath($config->get('config_delete_account_redirect_success'));
      $message = [
        'type_status' => 'status',
        'message' => $this->t('Account deleted'),
      ];
    }
    else {
      $destination = $this->getDestinationByPath($config->get('config_delete_account_redirect_fail'));
      $message = [
        'type_status' => 'error',
        'message' => $this->t('An unexpected error occurred. Please try again later or call the site admin.'),
      ];
    }

    // Show the message only if disable was't configured or unchecked.
    if (empty($config->get('config_delete_account_redirect_disable_message'))) {
      $this
        ->messenger()
        ->addMessage($message['message'], $message['type_status']);
    }

    // If was configured destination in success or fail in admin field.
    if (!empty($destination)) {
      $redirect = $destination->getRouteName();
    }

    return $this->redirect($redirect);
  }

  /**
   * Get destination by path.
   *
   * @param string $path
   *   The path to create Url object.
   *
   * @return \Drupal\Core\Url|null
   *   Url object or null.
   */
  private function getDestinationByPath($path) {
    try {
      $destination = Url::fromUserInput($path);
    }
    catch (\InvalidArgumentException $e) {
      return NULL;
    }

    // Indicates if this Url has a Drupal route.
    if ($destination->isRouted()) {
      return $destination;
    }

    return NULL;
  }

}
