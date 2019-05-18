<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Drupal\depcalc\Event\SectionComponentDependenciesEvent;
use Drupal\depcalc\FieldExtractor;
use Drupal\layout_builder\Section;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Subscribes to dependency collection to extract entities referenced on Layout Builder components.
 */
class LayoutBuilderFieldDependencyCollector extends BaseDependencyCollector {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * The DependencyCalculator constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface|null $layoutPluginManager
   *   The LayoutPluginManager
   */
  public function __construct(EventDispatcherInterface $dispatcher, LayoutPluginManagerInterface $layoutPluginManager = NULL) {
    $this->dispatcher = $dispatcher;
    $this->layoutPluginManager = $layoutPluginManager;

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the entities referenced on Layout Builder components.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if (!$this->layoutPluginManager) {
      return;
    }
    $entity = $event->getEntity();
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    $fields = FieldExtractor::getFieldsFromEntity($entity, [$this, 'fieldCondition']);
    foreach ($fields as $field) {
      foreach ($field as $item) {
        $section = $item->getValue()['section'];
        $this->addSectionDependencies($event, $section);
        $this->addComponentDependencies($event, $section->getComponents());
      }
    }
  }

  public function fieldCondition(ContentEntityInterface $entity, $field_name, FieldItemListInterface $field) {
    return in_array($field->getFieldDefinition()->getType(), [
      'layout_section'
    ]);
  }

  protected function addSectionDependencies(CalculateEntityDependenciesEvent $event, Section $section) {
    $layout_id = $section->getLayoutId();
    $layout_plugin_definition = $this->layoutPluginManager->getDefinition($layout_id);
    $event->setModuleDependencies([$layout_plugin_definition->getProvider()]);
  }

  /**
   * Adds dependencies from components.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   The components for this field.
   */
  protected function addComponentDependencies(CalculateEntityDependenciesEvent $event, array $components) {
    foreach ($components as $component) {
      $componentEvent = new SectionComponentDependenciesEvent($component);
      $this->dispatcher->dispatch(DependencyCalculatorEvents::SECTION_COMPONENT_DEPENDENCIES_EVENT, $componentEvent);
      $this->addSectionComponentEntityDependencies($event, $componentEvent->getEntityDependencies());
      $event->setModuleDependencies($componentEvent->getModuleDependencies());
    }
  }

  /**
   * Adds entity dependencies from this layout builder field to this event dependencies.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entity dependencies.
   */
  protected function addSectionComponentEntityDependencies(CalculateEntityDependenciesEvent $event, array $entities) {
    foreach ($entities as $entity) {
      $item_entity_wrapper = new DependentEntityWrapper($entity);
      $local_dependencies = [];
      $this->mergeDependencies($item_entity_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($item_entity_wrapper, $event->getStack(), $local_dependencies));
      $event->addDependency($item_entity_wrapper);
    }
  }

}
