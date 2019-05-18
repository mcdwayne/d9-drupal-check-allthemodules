<?php

namespace Drupal\bynder\EventSubscriber;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\UnableToAddUsageException;
use Drupal\bynder\Exception\UnableToDeleteUsageException;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Drupal\entity_usage\Events\Events;
use Drupal\paragraphs\ParagraphInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Listens for the usage events from Entity Usage module.
 */
class UsageEventSubscriber implements EventSubscriberInterface {

  /**
   * Bynder api service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   *   Bynder api service.
   */
  protected $bynderApi;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * UsageEventSubscriber constructor.
   *
   * @param \Drupal\bynder\BynderApiInterface $bynder_api_service
   *   Bynder api service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(BynderApiInterface $bynder_api_service, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->bynderApi = $bynder_api_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[Events::USAGE_ADD][] = ['onAdd'];
    $events[Events::USAGE_DELETE][] = ['onDelete'];
    return $events;
  }

  /**
   * Auxiliary function to get media information for asset usage operations.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *
   * @return array|null
   */
  private function getUsageEventMediainformation(EntityUsageEvent $event) {
    if ($event->getTargetEntityType() !== 'media') {
      return NULL;
    }

    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->entityTypeManager->getStorage('media')
      ->load($event->getTargetEntityId());
    if (!isset($media)) {
      return NULL;
    }

    $source_plugin = $media->getSource();
    if (!$source_plugin instanceof Bynder) {
      return NULL;
    }

    $url = NULL;
    if ($event->getReferencingEntityId()) {
      $entity = $this->entityTypeManager->getStorage($event->getReferencingEntityType())->load($event->getReferencingEntityId());

      // If the entity is a paragraph, attempt to recursively load the parent.
      while ($entity && $entity instanceof ParagraphInterface) {
        $entity = $entity->getParentEntity();
      }

      // If the entity exists and has a canonical link template, get the URL.
      if ($entity && $entity->hasLinkTemplate('canonical')) {
        $url = $entity->toUrl('canonical');
      }
    }

    if ($url) {
      return [
        'mediaId' => $source_plugin->getSourceFieldValue($media),
        'url' => $url,
      ];
    }
  }

  /**
   * Adds usage for Bynder asset.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *   The event to process.
   */
  public function onAdd(EntityUsageEvent $event) {
    if (\Drupal::service('module_handler')->moduleExists('entity_usage')) {
      $mediaInfo = $this->getUsageEventMediainformation($event);
      if (isset($mediaInfo)) {
        try {
          $this->bynderApi->addAssetUsage(
            $mediaInfo['mediaId'],
            $mediaInfo['url'],
            date(DATE_ISO8601, \Drupal::time()->getRequestTime()),
            'Added asset by user ' . \Drupal::currentUser()->getAccountName() . '.'
          );
        }
        catch (RequestException $e) {
          (new UnableToAddUsageException($e->getMessage()))->logException()->displayMessage();
        }
      }
    }
  }

  /**
   * Removes usage from Bynder asset.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *   The event to process.
   */
  public function onDelete(EntityUsageEvent $event) {

    $mediaInfo = $this->getUsageEventMediainformation($event);
    if (isset($mediaInfo['mediaId'])) {
      try {
        $this->bynderApi->removeAssetUsage(
          $mediaInfo['mediaId'],
          $mediaInfo['url']
        );
      }
      catch (RequestException $e) {
        (new UnableToDeleteUsageException($e->getMessage()))->logException()->displayMessage();
      }
    }
  }

}
