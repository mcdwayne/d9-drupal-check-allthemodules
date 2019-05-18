<?php

namespace Drupal\og_sm_path\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\og_sm\Event\SiteTypeEvent;
use Drupal\og_sm\Event\SiteTypeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the site type events.
 */
class SiteTypeSubscriber implements EventSubscriberInterface {

  /**
   * The pathauto pattern entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $pathautoPatternStorage;

  /**
   * Constructs a PathProcessorAlias object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->pathautoPatternStorage = $entity_type_manager->getStorage('pathauto_pattern');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SiteTypeEvents::ADD][] = 'onSiteTypeAdd';
    $events[SiteTypeEvents::REMOVE][] = 'onSiteTypeRemove';
    return $events;
  }

  /**
   * Event listener triggered when a site type is added.
   *
   * @param \Drupal\og_sm\Event\SiteTypeEvent $event
   *   The site event.
   */
  public function onSiteTypeAdd(SiteTypeEvent $event) {
    $node_type = $event->getNodeType();
    $pattern = $this->findPrimaryPattern($event->getNodeType());
    if (!$pattern) {
      /* @var \Drupal\pathauto\PathautoPatternInterface $pattern */
      $pattern = $this->pathautoPatternStorage->create([
        'id' => 'node_' . $node_type->id(),
        'label' => 'Node: ' . $node_type->label(),
        'type' => 'canonical_entities:node',
      ]);
      $node_type->getEntityType()->getLabel();
      $pattern->addSelectionCondition([
        'id' => 'entity_bundle:node',
        'bundles' => [$node_type->id() => $node_type->id()],
        'negate' => FALSE,
        'context_mapping' => [
          'node' => 'node',
        ],
      ]);
    }
    // Set the pathauto pattern for the add node type to [node:site-path].
    $pattern->setPattern('[node:site-path]')->save();
    $pattern->save();
  }

  /**
   * Event listener triggered when a site type is removed.
   *
   * @param \Drupal\og_sm\Event\SiteTypeEvent $event
   *   The site event.
   */
  public function onSiteTypeRemove(SiteTypeEvent $event) {
    $pattern = $this->findPrimaryPattern($event->getNodeType());
    if ($pattern) {
      // Restore the pathauto pattern back to the default pattern.
      $pattern->setPattern('content/[node:type]/[node:title]')->save();
    }
  }

  /**
   * Helper method that fetches a pathauto pattern based on a node type.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface|false
   *   The pathauto pattern, FALSE if no pattern was found.
   */
  protected function findPrimaryPattern(NodeTypeInterface $node_type) {
    $patterns = $this->pathautoPatternStorage->loadByProperties(['type' => 'canonical_entities:node']);

    foreach ($patterns as $pattern) {
      /* @var \Drupal\pathauto\PathautoPatternInterface $pattern */
      $default_bundles = [];
      foreach ($pattern->getSelectionConditions() as $condition_id => $condition) {
        if (in_array($condition->getPluginId(), ['entity_bundle:node', 'node_type'], TRUE)) {
          $default_bundles = $condition->getConfiguration()['bundles'];
        }
      }

      if ($node_type->id() === reset($default_bundles)) {
        return $pattern;
      }
    }
    return FALSE;
  }

}
