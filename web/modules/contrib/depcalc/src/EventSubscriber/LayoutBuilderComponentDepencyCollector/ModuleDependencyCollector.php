<?php

namespace Drupal\depcalc\EventSubscriber\LayoutBuilderComponentDepencyCollector;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\Event\SectionComponentDependenciesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to layout builder dependency collection to extract module dependencies.
 */
class ModuleDependencyCollector implements EventSubscriberInterface {

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

    if($plugin instanceof BlockPluginInterface) {
      $event->addModuleDependency($plugin->getConfiguration()['provider']);
    }
  }

}
