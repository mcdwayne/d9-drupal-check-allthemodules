<?php

/**
 * @file
 * Contains \Drupal\block_page\EventSubscriber\CurrentUserContext.
 */

namespace Drupal\block_page\EventSubscriber;

use Drupal\block_page\Event\BlockPageContextEvent;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sets the current user as a context.
 */
class CurrentUserContext implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new CurrentUserContext.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The account proxy.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(AccountProxyInterface $account_proxy, EntityManagerInterface $entity_manager) {
    $this->accountProxy = $account_proxy;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * Adds in the current user as a context.
   *
   * @param \Drupal\block_page\Event\BlockPageContextEvent $event
   *   The block page context event.
   */
  public function onBlockPageContext(BlockPageContextEvent $event) {
    $current_user = $this->userStorage->load($this->accountProxy->getAccount()->id());
    $context = new Context(array(
      'type' => 'entity:user',
      'label' => $this->t('Current user'),
    ));
    $context->setContextValue($current_user);
    $event->getBlockPage()->addContext('current_user', $context);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['block_page_context'][] = 'onBlockPageContext';
    return $events;
  }

}
