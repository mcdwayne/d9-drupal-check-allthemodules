<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;

class ConfigEntityDependencyCollector extends BaseDependencyCollector {

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
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates config entity dependencies.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof ConfigEntityInterface) {
      $wrapper = $event->getWrapper();
      $dependencies = $event->getDependencies();
      $entity_dependencies = $entity->getDependencies();
      if (isset($entity_dependencies['config'])) {
        $idKey = "{$entity->getEntityType()->getConfigPrefix()}.{$entity->get($entity->getEntityType()->getKey('id'))}";
        $key = array_search($idKey, $entity_dependencies['config']);
        if ($key !== FALSE) {
          unset($entity_dependencies['config'][$key]);
        }
      }
      if (!empty($entity_dependencies['content'])) {
        // @todo figure out how this is stored and iterate over it.
      }
      // Handle config and config entities.
      if (!empty($entity_dependencies['config'])) {
        foreach ($entity_dependencies['config'] as $dependency) {
          $sub_entity = $this->configManager->loadConfigEntityByName($dependency);
          if ($sub_entity) {
            $sub_wrapper = new DependentEntityWrapper($sub_entity);
            $local_dependencies = [];
            $sub_dependencies = $this->getCalculator()->calculateDependencies($sub_wrapper, $event->getStack(), $local_dependencies);
            unset($sub_dependencies['module']);
            $sub_wrapper->addDependencies($event->getStack(), ...array_values($sub_dependencies));
            $wrapper->addDependency($sub_wrapper, $event->getStack());
          }
          else {
            $dependencies['raw_config'][$dependency] = $this->configManager->getConfigFactory()->get($dependency);
          }
        }
      }
      $event->addDependency($wrapper);
      if (!empty($entity_dependencies['module'])) {
        $event->setModuleDependencies($entity_dependencies['module']);
      }
    }
  }

}