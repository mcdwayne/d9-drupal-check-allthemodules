<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\FieldExtractor;

/**
 * Link Field Collecter.
 *
 * Handles dependancy calculation of menu link fields.
 */
class LinkFieldCollector extends BaseDependencyCollector {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LinkFieldCollector constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates menu link dependancies.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    // Get the entity.
    $entity = $event->getEntity();

    // Confirm the entity is an instance of ContentEntityInterface.
    if ($entity instanceof ContentEntityInterface) {
      $fields = FieldExtractor::getFieldsFromEntity($entity, function (ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) {return $field->getFieldDefinition()->getType() === 'link';});
      if (!$fields) {
        return;
      }
      // Loop through entity fields.
      foreach ($fields as $field) {
        // Get event dependencies.
        /**
         * Loop through field items for relevant dependencies.
         *
         * @var \Drupal\link\Plugin\Field\FieldType\LinkItem $item
         */
        foreach ($field as $item) {
          // Check if link is external (no deps required).
          if ($item->isExternal()) {
            continue;
          }

          // Get values.
          $values = $item->getValue();

          // If values are empty, continue to next menu_link item.
          if (empty($values['uri'])){
            continue;
          }

          // Explode the uri first by a colon to retreive the link type.
          list($uri_type, $uri_referance) = explode(':', $values['uri'], 2);

          // URI handling switch.
          switch ($uri_type) {
            // Entity link.
            case 'entity';
              // Explode entity to get the type and id.
              list($entity_type, $entity_id) = explode('/', $uri_referance, 2);

              // Load the entity and wrap it.
              $uri_entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
              $entity_wrapper = new DependentEntityWrapper($uri_entity);

              // Merge and add dependancies.
              $local_dependencies = [];
              $this->mergeDependencies($entity_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($entity_wrapper, $event->getStack(), $local_dependencies));
              $event->addDependency($entity_wrapper);
              break;

            // Internal link.
            case 'internal':
              // TODO: ADD SUPPORT FOR INTERNAL LINKS.
              break;
          }
        }
      }
    }
  }

}
