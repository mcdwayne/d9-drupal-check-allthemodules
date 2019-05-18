<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Controller\HawkAuthController.
 */

namespace Drupal\hawk_auth\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\hawk_auth\HawkAuthCredentialsViewEvent;
use Drupal\hawk_auth\HawkAuthEvents;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserInterface;
use Drupal\hawk_auth\Entity\HawkCredentialStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;

/**
 * Contains the Controller for letting users view their hawk credentials.
 */
class HawkAuthController extends ControllerBase implements AccessInterface {

  /**
   * Hawk Credentials' storage.
   *
   * @var \Drupal\hawk_auth\Entity\HawkCredentialStorageInterface
   */
  protected $hawkCredentialStorage;

  /**
   * Users'' storage.
   *
   * @var UserStorageInterface
   */
  protected $userStorage;


  /**
   * Event dispatcher.
   *
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs Hawk controller object.
   *
   * @param HawkCredentialStorageInterface $hawk_credential_storage
   *   Storage model for managing Hawk Credentials' entities.
   * @param UserStorage $user_storage
   *   Storage model for users.
   * @param EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   */
  public function __construct(HawkCredentialStorageInterface $hawk_credential_storage, UserStorageInterface $user_storage, EventDispatcherInterface $event_dispatcher) {
    $this->hawkCredentialStorage = $hawk_credential_storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');

    return new static(
      $entity_manager->getStorage('hawk_credential'),
      $entity_manager->getStorage('user'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Displays an user's credentials which they can manipulate.
   *
   * @param UserInterface $user
   *   The user who's credentials is to be displayed.
   *
   * @return array
   *   Build structure for displaying a table of credentials.
   */
  public function credential(UserInterface $user) {
    /** @var \Drupal\hawk_auth\Entity\HawkCredentialInterface[] $credentials */
    $credentials = $this->hawkCredentialStorage->loadByProperties(array('uid' => $user->id()));

    $list = [];

    $list['credentials'] = [
      '#type' => 'table',
      '#header' => [
        'key_id' => [
          'data' => t('ID'),
        ],
        'key_secret' => [
          'data' => t('Key Secret'),
        ],
        'key_algo' => [
          'data' => t('Key Algorithm'),
        ],
        'operations' => [
          'data' => t('Operations'),
        ],
      ],
      '#rows' => [],
    ];

    foreach ($credentials as $credential) {
      $list['credentials']['#rows'][$credential->id()] = [
        'key_id' => $credential->id(),
        'key_secret' => $credential->getKeySecret(),
        'key_algo' => $credential->getKeyAlgo(),
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'permissions' => [
                'title' => t('Revoke Permissions'),
                'url' => Url::fromRoute('hawk_auth.user_credential_permissions', ['hawk_credential' => $credential->id()]),
              ],
              'delete' => [
                'title' => t('Delete'),
                'url' => Url::fromRoute('hawk_auth.user_credential_delete', ['hawk_credential' => $credential->id()]),
              ],
            ],
          ],
        ],
      ];
    }

    $event = new HawkAuthCredentialsViewEvent($user, $credentials, $list);
    $this->eventDispatcher->dispatch(HawkAuthEvents::VIEW_CREDENTIALS, $event);
    $list = $event->getBuild();

    return $list;
  }

  /**
   * Checks for access for viewing a user's hawk credentials.
   *
   * @param Route $route
   *    The route to check against.
   * @param RouteMatchInterface $route_match
   *    The current route being accessed.
   * @param AccountInterface $account
   *    The account currently logged in.
   *
   * @return AccessResultInterface
   *   Access Result whether the user can see the credentials or not.
   */
  public function accessView(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var AccountInterface $user */
    $user = $route_match->getParameter('user');

    return AccessResult::allowedIf(
      $this->checkUserAccessForView($user, $account)
    );
  }

  /**
   * Internal function for checking view permission for the current user.
   *
   * @param AccountInterface $user_owner
   *   The user whose credentials are being viewed.
   * @param AccountInterface $user_viewer
   *   The user who is viewing the credentials.
   *
   * @return bool
   *   True or false.
   */
  public static function checkUserAccessForView(AccountInterface $user_owner, AccountInterface $user_viewer) {
    return
      $user_viewer->hasPermission('administer hawk') ||
      ($user_viewer->hasPermission('access own hawk credentials') && $user_viewer->id() == $user_owner->id());

  }

  /**
   * Checks whether an user can create credentials or not.
   *
   * @param Route $route
   *    The route to check against.
   * @param RouteMatchInterface $route_match
   *    The current route being accessed.
   * @param AccountInterface $account
   *    The account currently logged in.
   *
   * @return AccessResultInterface
   *   The result of check.
   */
  public function accessCreate(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var AccountInterface $user */
    $user = $route_match->getParameter('user');
    if (!($user instanceof AccountInterface)) {
      $user = $this->userStorage->load($user);
      if (empty($user)) {
        return AccessResult::forbidden();
      }
    }

    if ($account->hasPermission('administer hawk')) {
      return AccessResult::allowed();
    }
    else if ($account->hasPermission('add own hawk credentials') && $user->id() == $account->id()) {
      $max = hawk_get_max_credentials($account);
      if ($max > 0) {
        $credentials = $this->hawkCredentialStorage->loadByProperties(['uid' => $account->id()]);
        $count = count($credentials);
        if ($count < $max) {
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

}
