<?php

namespace Drupal\medium_posts\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\medium_posts\MediumPostsManagerInterface;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class WorkbenchModerationSubscriber.
 *
 * @package Drupal\medium_posts\EventSubscriber
 */
class WorkbenchModerationSubscriber implements EventSubscriberInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Medium posts manager.
   *
   * @var \Drupal\medium_posts\MediumPostsManagerInterface
   */
  protected $mediumPostsManager;

  /**
   * WorkbenchModerationSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\medium_posts\MediumPostsManagerInterface $medium_posts_manager
   *   Medium publish manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MediumPostsManagerInterface $medium_posts_manager) {
    $this->configFactory = $config_factory;
    $this->mediumPostsManager = $medium_posts_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events['workbench_moderation.state_transition'][] = ['pushToMedium'];
    return $events;
  }

  /**
   * Push node to medium.com.
   *
   * @param \Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent $event
   *   Workbench moderation transition event.
   */
  public function pushToMedium(WorkbenchModerationTransitionEvent $event) {
    $medium_posts_settings = $this->configFactory->get('medium_posts.settings');

    // If disabled, skip and do nothing.
    if (!$medium_posts_settings->get('push_on_workbench_moderation_status')) {
      return;
    }

    if ($event->getStateAfter() === $medium_posts_settings->get('workbench_moderation_publish_status')) {
      // Get the node from event.
      $node = $event->getEntity();

      // Do only when the node type is selected as medium post.
      if (!$this->mediumPostsManager->isMediumNodeType($node)) {
        return;
      }

      // If a node content has been published on Medium already, we shouldn't
      // publish it again.
      if ($this->mediumPostsManager->isPublished($node->uuid())) {
        return;
      }

      $this->mediumPostsManager->publish($node);
    }
  }

}
