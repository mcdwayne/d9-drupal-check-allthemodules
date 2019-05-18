<?php

namespace Drupal\acquia_contenthub\EventSubscriber\SerializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TermParentSerializer.
 *
 * @package Drupal\acquia_contenthub\EventSubscriber\SerializeContentField
 */
class TermParentSerializer implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * TermParentSerializer constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD][] = ['onSerializeContentField', 102];
    return $events;
  }

  /**
   * Reacts on SERIALIZE_CONTENT_ENTITY_FIELD event.
   *
   * Manages taxonomy terms relationships.
   *
   * @param \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent $event
   *   Event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onSerializeContentField(SerializeCdfEntityFieldEvent $event) {
    if ($event->getEntity()->getEntityTypeId() == 'taxonomy_term' && $event->getFieldName() == 'parent') {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $event->getEntity();
      /** @var \Drupal\taxonomy\TermStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $parents = $storage->loadParents($term->id());
      // Set the value of the parent for other subscribers to handle.
      if (!empty($parents)) {
        $term->set('parent', $parents);
      }
    }
  }

}
