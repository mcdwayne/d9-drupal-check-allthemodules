<?php

namespace Drupal\druminate_sso\Subscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\druminate\Plugin\DruminateEndpointManager;
use Drupal\druminate_sso\Event\DruminateSsoEvents;
use Drupal\druminate_sso\Event\DruminateSsoPreLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Event Subscriber for Druminate SSO triggered login events.
 *
 * @package Drupal\druminate_sso\Subscriber
 */
class DruminateSsoPreLoginSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The Druminate API Plugin Manager.
   *
   * @var \Drupal\druminate\Plugin\DruminateEndpointManager
   */
  protected $druminateEndpointManager;

  /**
   * ExternalAuthSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory instance.
   * @param \Drupal\druminate\Plugin\DruminateEndpointManager $druminateEndpointManager
   *   The Druminate API endpoint manager.
   */
  public function __construct(ConfigFactoryInterface $config, DruminateEndpointManager $druminateEndpointManager) {
    $this->settings = $config->get('druminate_sso.settings');
    $this->druminateEndpointManager = $druminateEndpointManager;
  }

  /**
   * Entrypoint for the pre-login event.
   *
   * @param \Drupal\druminate_sso\Event\DruminateSsoPreLoginEvent $event
   *   The pre-login event.
   */
  public function preLogin(DruminateSsoPreLoginEvent $event) {
    $params = [
      'cons_id' => $event->getConsId(),
      'sso_auth_token' => $event->getToken(),
    ];

    // Get User Groups for a constituent in LO.
    /** @var \Drupal\Druminate\Plugin\DruminateEndpointInterface $group_endpoint */
    $group_endpoint = $this->druminateEndpointManager->createInstance('sso_user_groups', $params);
    $data = $group_endpoint->loadData();
    if (is_object($data) && isset($data->getConsGroupsResponse)) {
      // Valid response.
      $roles = $this->getMappedRoles($data->getConsGroupsResponse->group);
      if (!empty($roles)) {
        $account = $event->getAccount();
        foreach ($roles as $role) {
          $account->addRole($role);
        }
      }
      elseif ($this->settings->get('role.deny_no_match')) {
        $event->setAuthRestricted(TRUE);
      }
    }
    // An errorResponse in $data means we failed to login for some reason.
    elseif (is_object($data) && isset($data->errorResponse)) {
      $message = $this->t('An error occurred. Please see your Administrator and mention error code: %error', ['%error' => $data->errorResponse->code]);
      $this->getLogger('druminate_sso')->error($message);
    }
    // Catch the false return by the Druminate Endpoint.
    elseif (!$data) {
      $this->getLogger('druminate_sso')
        ->error($this->t('An unknown error occurred. Please see your Administrator.'));
    }
  }

  /**
   * Determine which roles will be mapped.
   *
   * @param array $attributes
   *   The properties to determine which roles to map.
   *
   * @return array
   *   An array of rids that should be added.
   */
  protected function getMappedRoles(array $attributes = NULL) {
    if (empty($attributes)) {
      return [];
    }

    $role_map = $this->settings->get('role.role_mapping');
    if (empty($role_map)) {
      return [];
    }

    $roles_added = [];
    $attr = 'label';
    foreach ($role_map as $condition) {

      switch ($condition['method']) {

        case 'match':
          foreach ($attributes as $attribute) {
            $value = $attribute->$attr;
            if (strtolower($value) === strtolower($condition['group'])) {
              $roles_added[] = $condition['rid'];
            }
          }
          break;

        case 'contains':
          foreach ($attributes as $attribute) {
            $value = $attribute->$attr;
            if (strpos($value, $condition['group']) !== FALSE) {
              $roles_added[$condition['rid']] = $condition['rid'];
            }
          }
          break;

      }
    }

    return $roles_added;
  }

  /**
   * This method is called whenever the KernelEvents::REQUEST is dispatched.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The request event.
   */
  public function checkForUserRedirection(GetResponseEvent $event) {
    if (!\Drupal::currentUser()->isAnonymous() && $event->getRequest()->getRequestUri() === '/druminate/login') {
      $response = new RedirectResponse("/user");
      return $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      DruminateSsoEvents::PRE_LOGIN_EVENT => ['preLogin'],
      KernelEvents::REQUEST => ['checkForUserRedirection'],
    ];
  }

}
