<?php

namespace Drupal\quicker_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\quicker_login\Service\QuickerLoginService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuickerLoginController.
 *
 * @package Drupal\quicker_login\Controller
 */
class QuickerLoginController extends ControllerBase {

  /**
   * The quicker login service.
   *
   * @var Drupal\quicker_login\Service\QuickerLoginService
   */
  protected $quickerLoginService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicker_login.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(QuickerLoginService $quicker_login_service) {
    $this->quickerLoginService = $quicker_login_service;
  }

  /**
   * Log in the user.
   */
  public function login($user_name) {
    $successful = $this->quickerLoginService->loginUserName($user_name);
    if ($successful) {
      return $this->redirect('user.page');
    }
    else {
      drupal_set_message($this->t('There is no such user @user_name', ['@user_name' => $user_name]), 'error');

      return $this->redirect('<front>');
    }
  }

}
