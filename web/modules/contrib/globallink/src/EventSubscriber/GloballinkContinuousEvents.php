<?php

namespace Drupal\globallink\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\tmgmt\Events\ShouldCreateJobEvent;
use Drupal\tmgmt\Events\ContinuousEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for tmgmt events.
 */
class GloballinkContinuousEvents implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * GloballinkContinuousEvents constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PathMatcherInterface $path_matcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Do not add the job if we have a filter match.
   *
   * @param \Drupal\tmgmt\Events\ShouldCreateJobEvent $event
   *   The event object.
   */
  public function onShouldCreateJob(ShouldCreateJobEvent $event) {
    $job = $event->getJob();
    $item_type = $event->getItemType();
    $item_id = $event->getItemId();

    // Filter out content.
    if ($event->getPlugin() == 'content' && $event->getJob()->isContinuous()) {
      $storage = $this->entityTypeManager->getStorage($item_type);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->load($item_id);

      if (!$entity) {
        return;
      }

      /** @var array $filters_table */
      $filters_table = $job->getSetting('filters_table');

      if (empty($filters_table['filters']) || !is_array($filters_table['filters'])) {
        return;
      }

      foreach ($filters_table['filters'] as $filter) {
        // @todo Find a way to not save settings without value.
        if (empty($filter['value'])) {
          continue;
        }
        // Url filter.
        switch ($filter['field']) {
          case 'url':
            if ($entity->hasField('path') && $entity->get('path')->alias && $this->pathMatcher->matchPath($entity->get('path')->alias, $filter['value'])) {
              $event->setShouldCreateItem(FALSE);
              $job->addMessage('Item type @type with id @id skipped due to URL starts with filter rule.', [
                '@type' => $item_type,
                '@id' => $item_id,
              ], 'debug'
              );
              return;
            }

            break;
          case 'id':
            if ($filter['value'] == $entity->id()) {
              $event->setShouldCreateItem(FALSE);
              $job->addMessage('Item type @type with id @id skipped due to URL contains filter rule.', [
                '@type' => $item_type,
                '@id' => $item_id,
              ], 'debug'
              );
              return;
            }
            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContinuousEvents::SHOULD_CREATE_JOB][] = ['onShouldCreateJob'];
    return $events;
  }
}
