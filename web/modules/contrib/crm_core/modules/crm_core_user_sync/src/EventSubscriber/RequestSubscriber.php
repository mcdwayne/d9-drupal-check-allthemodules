<?php

namespace Drupal\crm_core_user_sync\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CRM Core User Synchronization event subscriber.
 */
class RequestSubscriber implements EventSubscriberInterface {

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Relation service.
   *
   * @var \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface
   */
  protected $relationService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current logged in user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface $relation_service
   *   The relation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory, CrmCoreUserSyncRelationInterface $relation_service, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->relationService = $relation_service;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($this->currentUser->isAuthenticated()) {
      $config = $this->configFactory->get('crm_core_user_sync.settings');
      if ($config->get('contact_load')) {
        $individual_id = $this->relationService->getIndividualIdFromUserId($this->currentUser->id());
        if ($individual_id) {
          $individual = $this->entityTypeManager->getStorage('crm_core_individual')->load($individual_id);
          $account = $this->currentUser->getAccount();
          $account->crm_core['contact'] = $individual;
          $this->currentUser->setAccount($account);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onKernelRequest',
    ];
  }

}
