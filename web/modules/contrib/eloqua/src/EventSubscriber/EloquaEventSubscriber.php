<?php

/**
 * @file
 * Contains Drupal\eloqua\EventSubscriber\EloquaEventSubscriber
 */

namespace Drupal\eloqua\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a response event subscriber for Eloqua. Sets config value for use
 * in eloqua.module.
 */
class EloquaEventSubscriber implements EventSubscriberInterface {

  /**
   * The Eloqua config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionManager;

  /**
   * Creates a new EloquaEventSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(AccountInterface $account, EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, ExecutableManagerInterface $condition_manager) {
    $this->account = $account;
    $this->userStorage = $entity_manager->getStorage('user');
    $this->config = $config_factory->get('eloqua.settings');
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('evaluateTrackingScope');
    return $events;
  }

  /**
   * Evaluates tracking scoping conditions and sets state setting.
   * 
   * @param GetResponseEvent $event
   */
  public function evaluateTrackingScope(GetResponseEvent $event) {
    if (null !== $this->config->get('site_identifier')) {
      $user_role_config = $this->config->get('user_roles');
      $current_user = $this->userStorage->load($this->account->id());

      /* @var \Drupal\user\Plugin\Condition\UserRole $condition */
      $user_role = $this->conditionManager->createInstance('user_role')
        ->setConfig('roles', $user_role_config['roles'])
//      ->setConfig('negate',  $user_role_config['negate'])
        ->setContextValue('user', $current_user);
      $user_role_result = $user_role->evaluate();

      /* @var \Drupal\system\Plugin\Condition\RequestPath $request_path */
      $request_path = $this->conditionManager->createInstance('request_path');
      $request_path->setConfiguration($this->config->get('request_path'));

      // Negate request_path if applicable.
      $request_path_result = ($request_path->isNegated()) ? !$request_path->evaluate() : $request_path->evaluate();

      if ($user_role_result && $request_path_result) {
        \Drupal::state()->set('eloqua.condition_result', 1);
      }
      else {
        \Drupal::state()->set('eloqua.condition_result', 0);
      }
    }
  }
}
