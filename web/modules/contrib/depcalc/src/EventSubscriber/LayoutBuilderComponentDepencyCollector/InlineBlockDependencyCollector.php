<?php

namespace Drupal\depcalc\EventSubscriber\LayoutBuilderComponentDepencyCollector;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\Event\SectionComponentDependenciesEvent;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to layout builder dependency collection to extract inline block dependencies.
 */
class InlineBlockDependencyCollector implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TextItemFieldDependencyCollector constructor.
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
    $events[DependencyCalculatorEvents::SECTION_COMPONENT_DEPENDENCIES_EVENT][] = ['onCalculateSectionComponentDependencies'];
    return $events;
  }

  /**
   * Calculates the entities referenced on Layout Builder components.
   *
   * @param \Drupal\depcalc\Event\SectionComponentDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateSectionComponentDependencies(SectionComponentDependenciesEvent $event) {

    $component = $event->getComponent();
    $plugin = $component->getPlugin();

    if(!$plugin instanceof InlineBlock) {
      return;
    }

    $revision_id = $plugin->getConfiguration()['block_revision_id'];
    $entity = $this->entityTypeManager->getStorage('block_content')->loadRevision($revision_id);
    $event->addEntityDependency($entity);
  }

}
