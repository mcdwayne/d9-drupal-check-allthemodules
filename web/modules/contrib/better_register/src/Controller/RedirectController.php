<?php

namespace Drupal\better_register\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserStorageInterface;

/**
 * Class RedirectController.
 *
 * @package Drupal\better_register\Controller
 */
class RedirectController extends ControllerBase {

  protected $currentUser;
  protected $userStorage;

  /**
   * Implemets the constuct for create class object.
   */
  public function __construct(AccountProxyInterface $current_user, UserStorageInterface $user_storage) {
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
  }

  /**
   * Create dependency injection for the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user')
    );

  }

  public function toUserEditPage() {
    $user_id = $this->currentUser->id();

    if ($user_id) {
      $user_entity = $this->userStorage->load($user_id);
      return new RedirectResponse($user_entity->toUrl('edit-form')->toString());
    }
    else {
      return new RedirectResponse('/user/login');
    }
  }

}
