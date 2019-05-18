<?php

namespace Drupal\pardot\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides a response event subscriber for Pardot.
 *
 * Sets config value for use in pardot.module.
 */
class PardotEventSubscriber implements EventSubscriberInterface {

  /**
   * The Pardot settings configuration.
   */
  private $config;

  /**
   * User storage.
   */
  private $user_storage;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  private $condition_manager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $account;

  /**
   * Creates a new PardotEventSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account, EntityTypeManagerInterface $entity_manager, ExecutableManagerInterface $condition_manager) {
    $this->config = $config_factory->get('pardot.settings');
    $this->account = $account;
    $this->user_storage = $entity_manager->getStorage('user');
    $this->condition_manager = $condition_manager;
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
    // Check if Pardot is configured with an account ID.
    if (null !== $this->config->get('account_id')) {
      // Load use role condition configuration and current user.
      $user_role_condition = $this->config->get('user_role_condition');
      $current_user = $this->user_storage->load($this->account->id());

      // Create user_role condition with Pardot user condition configuration.
      // Evaluate using current user context.
      $user_role = $this->condition_manager->createInstance('user_role')
        ->setConfig('roles', $user_role_condition['roles'])
        ->setContextValue('user', $current_user);
      $user_role_result = $user_role->evaluate();

      // Create request_path condition with Pardot path condition configuration.
      $request_path = $this->condition_manager->createInstance('request_path');
      $request_path->setConfiguration($this->config->get('path_condition'));

        // Negate request_path_evaluate() if applicable.
      $is_negated = $request_path->isNegated();
      $request_path_result = ($request_path->isNegated()) ? !$request_path->evaluate() : $request_path->evaluate();

      if ($user_role_result && $request_path_result) {
        \Drupal::state()->set('pardot.include_tracking', 1);
      }
      else {
        \Drupal::state()->set('pardot.include_tracking', 0);
      }
    }
  }
}
