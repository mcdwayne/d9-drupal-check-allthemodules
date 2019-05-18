Excluding Data from the Export Process
======================================

Syndicating data has a number of important nuances that must be address. Amongst these potential problems is the problem of preventing certain types of data from being excluded. ContentHub has two mechanisms by which to prevent data from syndicating to the ContentHub Service. One is relatively simple and straight forward and has no real implications. The other can be difficult to untangle logically. Lets look at both in turn and evaluate when and how to take advantage of them.

Preventing Enqueuing
^^^^^^^^^^^^^^^^^^^^

ContentHub keys off of any entity save or update. This means that even something like a view, block or menu changing is noticed and queued by ContentHub. Adding a new field, rearranging how fields display on your entity_view_display entities and virtually any other action which could touch an entity (content or configuration) will trigger ContentHub. This is desired under most circumstance, but in certain situations it may not actually be desirable. When these situation arise, your first tool should be the ``\Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent`` event. Any event subscriber listening to ``\Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents::ENQUEUE_CANDIDATE_ENTITY`` is probably a good example to read from to get an idea for the different possibilities. These classes should provide fairly simple examples of how to exclude certain kinds of data as desired.

As an example, ContentHub should never attempt to syndicate "temporary" files. In order to facilitate this, we have a class which prevents creation of new files from ever triggering the enqueue process. The code is provided below for reference.

.. code-block:: php

    <?php

    namespace Drupal\acquia_contenthub_publisher\EventSubscriber\EnqueueEligibility;

    use Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents;
    use Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent;
    use Drupal\file\FileInterface;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    /**
     * Subscribes to entity eligibility to prevent enqueueing temporary files.
     */
    class FileIsTemporary implements EventSubscriberInterface {

      /**
       * {@inheritdoc}
       */
      public static function getSubscribedEvents() {
        $events[ContentHubPublisherEvents::ENQUEUE_CANDIDATE_ENTITY][] = ['onEnqueueCandidateEntity', 50];
        return $events;
      }

      /**
       * Prevent temporary files from enqueueing.
       *
       * @param \Drupal\acquia_contenthub_publisher\Event\ContentHubEntityEligibilityEvent $event
       *   The event to determine entity eligibility.
       */
      public function onEnqueueCandidateEntity(ContentHubEntityEligibilityEvent $event) {
        // If this is a file with status = 0 (TEMPORARY FILE) do not export it.
        // This is a check to avoid exporting temporary files.
        $entity = $event->getEntity();
        if ($entity instanceof FileInterface && $entity->isTemporary()) {
          $event->setEligibility(FALSE);
          $event->stopPropagation();
        }
      }

    }

Preventing Entity Syndication
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

While preventing the enqueuing of data under certain circumstances is likely to be a good idea, it is not alway sufficient. The dependency calculation process can and will match to any entity it identifies during its calculation process, and that means entities which might not normally cause an enqueue process to happen can still end up in the ContentHub Service by virtue of the fact that they can be calculated to via some sort of relationship to data that is desirable to export.

The ContentHub modules have another use case for this particular API. When exporting data, we don't want to export the same data multiple times if it hasn't changed. In order to facilitate this, we keep a hash of the entity, and use it for comparison during the export process. If we've exported a given entity at the current hash before, there's no reason to export it again. This particular use case is much more naive than others in this category because in our case the data still goes to the service, it just went on a previous request.

The single largest pitfall of using this element of the API is the dependency tracking. If you need to remove a particular type of entity, you will have to ensure that you removed unnecessary dependencies that entity had as well, otherwise you could end up with very strange patterns of data communication and no obvious connection between certain syndicated data entities.

The ContentHub use of this API is included below for reference:

.. code-block:: php

    <?php

    namespace Drupal\acquia_contenthub_publisher\EventSubscriber\PublishEntities;

    use Drupal\acquia_contenthub_publisher\ContentHubPublisherEvents;
    use Drupal\acquia_contenthub_publisher\Event\ContentHubPublishEntitiesEvent;
    use Drupal\acquia_contenthub_publisher\PublisherTracker;
    use Drupal\Core\Database\Connection;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class RemoveUnmodifiedEntities implements EventSubscriberInterface {

      /**
       * The database connection.
       *
       * @var \Drupal\Core\Database\Connection
       */
      protected $database;

      /**
       * RemoveUnmodifiedEntities constructor.
       *
       * @param \Drupal\Core\Database\Connection $database
       *   The database connection.
       */
      public function __construct(Connection $database) {
        $this->database = $database;
      }

      /**
       * {@inheritdoc}
       */
      public static function getSubscribedEvents() {
        $events[ContentHubPublisherEvents::PUBLISH_ENTITIES][] = ['onPublishEntities', 1000];
        return $events;
      }

      /**
       * Removes unmodified entities before publishing.
       *
       * @param \Drupal\acquia_contenthub_publisher\Event\ContentHubPublishEntitiesEvent $event
       */
      public function onPublishEntities(ContentHubPublishEntitiesEvent $event) {
        $dependencies = $event->getDependencies();
        $uuids = array_keys($dependencies);
        $query = $this->database->select('acquia_contenthub_publisher_export_tracking', 't')
          ->fields('t', ['entity_uuid', 'hash']);
        $query->condition('t.entity_uuid', $uuids, 'IN');
        $query->condition('t.status', [PublisherTracker::CONFIRMED, PublisherTracker::EXPORTED], 'IN');
        $results = $query->execute();
        foreach ($results as $result) {
          // Can't check it if it doesn't have a hash.
          // @todo make this a query.
          if (!$result->hash) {
            continue;
          }
          $wrapper = $dependencies[$result->entity_uuid];
          if ($wrapper->getHash() == $result->hash) {
            $event->removeDependency($result->entity_uuid);
          }
        }
      }

    }
