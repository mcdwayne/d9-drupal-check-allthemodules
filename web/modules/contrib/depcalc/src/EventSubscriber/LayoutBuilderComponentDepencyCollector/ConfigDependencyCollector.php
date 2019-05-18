<?php

namespace Drupal\depcalc\EventSubscriber\LayoutBuilderComponentDepencyCollector;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\Event\SectionComponentDependenciesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to layout builder dependency collection to extract config dependencies.
 */
class ConfigDependencyCollector implements EventSubscriberInterface {

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * ConfigEntityDependencyCollector constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
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

    $config_dependencies = $plugin->getPluginDefinition()['config_dependencies']['config'] ?? [];
    foreach ($config_dependencies as $config_dependency){
      $config_entity = $this->configManager->loadConfigEntityByName($config_dependency);
      $event->addEntityDependency($config_entity);
    }
  }
}
