<?php

namespace Drupal\media_acquiadam_report\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\Events\Events;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subscribe to EntityUsage events.
 */
class AcquiadamUsageSubscriber implements EventSubscriberInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a AcquiadamUsageSubscriber object.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Respond when entity usage records are added/updated.
    return [
      Events::USAGE_REGISTER => ['mediaUsageChange']
    ];
  }

  /**
   * This method is called whenever an EntityUsage event is dispatched.
   *
   * @param EntityUsageEvent $event
   */
  public function mediaUsageChange(EntityUsageEvent $event) {
    if ($event->getTargetEntityType() == 'media') {
      $mid = $event->getTargetEntityId();
      $asset_id_fields = media_acquiadam_get_bundle_asset_id_fields();
      $media = $this->entityTypeManager->getStorage('media')->load($mid);
      $media_bundle = $media->bundle();

      // Clear cache tag on asset listing so entity usage count is up to date.
      if (array_key_exists($media_bundle, $asset_id_fields)){
        Cache::invalidateTags(['config:views.view.acquia_dam_reporting']);
      }
    }
  }
}
