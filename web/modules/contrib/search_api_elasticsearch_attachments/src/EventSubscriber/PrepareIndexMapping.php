<?php

namespace Drupal\search_api_elasticsearch_attachments\EventSubscriber;

use Drupal\elasticsearch_connector\Event\PrepareIndexMappingEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api_elasticsearch_attachments\Helpers;
use Drupal\search_api\Entity\Index;

/**
 * {@inheritdoc}
 */
class PrepareIndexMapping implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[PrepareIndexMappingEvent::PREPARE_INDEX_MAPPING] = 'indexMapping';
    return $events;
  }

  /**
   * Method to prepare index mapping.
   *
   * @param \Drupal\elasticsearch_connector\Event\PrepareIndexMappingEvent $event
   *   The PrepareIndexMappingEvent event.
   */
  public function indexMapping(PrepareIndexMappingEvent $event) {
    // We need to react only on our processor.
    $indexName = Helpers::getIndexName($event->getIndexName());
    $processors = Index::load($indexName)->getProcessors();
    // Exclude field only if processor is enabled.
    if (!empty($processors['elasticsearch_attachments'])) {
      $indexMappingParams = $event->getIndexMappingParams();
      // Exclude our source encoded data field from getting saved in ES.
      $indexMappingParams['body'][$indexMappingParams['type']]['_source']['excludes'][] = 'es_attachment.data';
      $event->setIndexMappingParams($indexMappingParams);
    }
  }

}
