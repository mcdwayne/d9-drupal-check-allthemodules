<?php

namespace Drupal\replicate_group\EventSubscriber;

/**
 * @file
 * Contains \Drupal\replicate\EventSubscriber\ReplicateGroupSubscriber.
 */

use Drupal\Core\Session\AccountProxy;
use Drupal\group\GroupMembershipLoader;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\replicate\Events\AfterSaveEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReplicateGroupSubscriber.
 *
 * @package Drupal\replicate\EventSubscriber
 */
class ReplicateGroupSubscriber implements EventSubscriberInterface {

  /**
   * The entity manager used for loading group entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $membershipLoader;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $account;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * ReplicateGroupSubscriber constructor.
   *
   * @param \Drupal\group\GroupMembershipLoader $membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The current user.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler plugin manager.
   */
  public function __construct(GroupMembershipLoader $membership_loader, AccountProxy $account, GroupContentEnablerManagerInterface $plugin_manager) {
    $this->membershipLoader = $membership_loader;
    $this->account = $account;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Relate the replicated node to the same group(s) on behalf of the user.
   *
   * @param \Drupal\replicate\Events\AfterSaveEvent $event
   *   The event we're working on.
   */
  public function relateToGroup(AfterSaveEvent $event) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $event->getEntity();

    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      if ($entity->getEntityTypeId() == $plugin->getEntityTypeId() && $entity->bundle() == $plugin->getEntityBundle()) {
        foreach ($this->membershipLoader->loadByUser(($this->account)) as $group_membership) {
          $group = $group_membership->getGroup();
          $group->addContent($entity, $plugin_id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::AFTER_SAVE][] = 'relateToGroup';
    return $events;
  }

}
