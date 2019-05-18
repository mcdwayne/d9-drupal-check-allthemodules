<?php

namespace Drupal\acquia_contenthub;

use Acquia\ContentHubClient\CDFDocument;
use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\Event\CdfAttributesEvent;
use Drupal\acquia_contenthub\Event\CreateCdfEntityEvent;
use Drupal\acquia_contenthub\Event\EntityDataTamperEvent;
use Drupal\acquia_contenthub\Event\EntityImportEvent;
use Drupal\acquia_contenthub\Event\FailedImportEvent;
use Drupal\acquia_contenthub\Event\LoadLocalEntityEvent;
use Drupal\acquia_contenthub\Event\ParseCdfEntityEvent;
use Drupal\acquia_contenthub\Event\PruneCdfEntitiesEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\depcalc\DependencyCalculator;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\DependentEntityWrapperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Serialize an entity to a CDF format.
 *
 * This class will convert an array of entities into a CDF compatible array of
 * data.
 */
class EntityCdfSerializer {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The dependency calculator.
   *
   * @var \Drupal\depcalc\DependencyCalculator
   */
  protected $calculator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * EntityCdfSerializer constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\depcalc\DependencyCalculator $calculator
   *   The dependency calculator.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   */
  public function __construct(EventDispatcherInterface $dispatcher, ConfigFactoryInterface $config_factory, DependencyCalculator $calculator, ModuleInstallerInterface $module_installer) {
    $this->dispatcher = $dispatcher;
    $this->configFactory = $config_factory;
    $this->calculator = $calculator;
    $this->moduleInstaller = $module_installer;
  }

  /**
   * Serialize an array of entities into CDF format.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface[] $dependencies
   *   The entity dependency wrappers.
   *
   * @return \Acquia\ContentHubClient\CDF\CDFObject[]
   *   List of CDF objects.
   */
  public function serializeEntities(DependentEntityWrapperInterface ...$dependencies) {
    $output = [];
    foreach ($dependencies as $wrapper) {
      $entity = $wrapper->getEntity();
      $wrapper_dependencies = [];
      if ($entity_dependencies = $wrapper->getDependencies()) {
        $wrapper_dependencies['entity'] = $entity_dependencies;
      }
      if ($module_dependencies = $wrapper->getModuleDependencies()) {
        // Prevent unnecessary string keys.
        $wrapper_dependencies['module'] = array_values($module_dependencies);
      }
      $event = new CreateCdfEntityEvent($entity, $wrapper_dependencies);
      $this->dispatcher->dispatch(AcquiaContentHubEvents::CREATE_CDF_OBJECT, $event);
      foreach ($event->getCdfList() as $cdf) {
        $attributesEvent = new CdfAttributesEvent($cdf, $entity);
        $this->dispatcher->dispatch(AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES, $attributesEvent);
        $output[] = $cdf;
      }
    }
    return $output;
  }

  /**
   * Unserializes a CDF into a list of Drupal entities.
   *
   * @todo add more docs about the expected CDF format.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF Document.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function unserializeEntities(CDFDocument $cdf, DependencyStack $stack) {
    if (!$cdf->hasEntities()) {
      throw new \Exception("Missing CDF Entities entry. Not a valid CDF.");
    }
    $event = new PruneCdfEntitiesEvent($cdf);
    $this->dispatcher->dispatch(AcquiaContentHubEvents::PRUNE_CDF, $event);
    $cdf = $event->getCdf();
    $this->handleModules($cdf, $stack);
    // Allows entity data to be manipulated before unserialization.
    $event = new EntityDataTamperEvent($cdf, $stack);
    $this->dispatcher->dispatch(AcquiaContentHubEvents::ENTITY_DATA_TAMPER, $event);
    $cdf = $event->getCdf();
    // Organize the entities into a dependency chain.
    // Use a while loop to prevent memory expansion due to recursion.
    while (!$stack->hasDependencies(array_keys($cdf->getEntities()))) {
      // @todo add tracking to break out of the while loop when dependencies cannot be further processed.
      $count = count($stack->getDependencies());
      foreach ($cdf->getEntities() as $uuid => $entity_data) {
        if ((!$stack->hasDependency($uuid) || $stack->getDependency($uuid)->needsAdditionalProcessing()) && $this->entityIsProcessable($entity_data, $stack)) {
          $event = new LoadLocalEntityEvent($entity_data, $stack);
          $this->dispatcher->dispatch(AcquiaContentHubEvents::LOAD_LOCAL_ENTITY, $event);
          $event = new ParseCdfEntityEvent($entity_data, $stack, $event->getEntity());
          $this->dispatcher->dispatch(AcquiaContentHubEvents::PARSE_CDF, $event);
          $entity = $event->getEntity();
          if ($entity) {
            $entity->save();
            $wrapper = new DependentEntityWrapper($entity);
            // Config uuids can be more fluid since they can match on id.
            if ($wrapper->getUuid() != $uuid) {
              $wrapper->setRemoteUuid($uuid);
            }
            $stack->addDependency($wrapper);
            if ($entity->isNew()) {
              $event_name = AcquiaContentHubEvents::ENTITY_IMPORT_NEW;
              $event = new EntityImportEvent($entity, $entity_data);
            }
            else {
              $event_name = AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE;
              $event = new EntityImportEvent($entity, $entity_data);
            }
            $this->dispatcher->dispatch($event_name, $event);
          }
          else {
            // Remove CDF Entities that were processable but didn't resolve into
            // an entity.
            $cdf->removeCdfEntity($uuid);
          }
        }
      }
      if ($count === count($stack->getDependencies())) {
        // @todo get import failure logging and tracking working.
        $event = new FailedImportEvent($cdf, $stack, $count);
        $this->dispatcher->dispatch(AcquiaContentHubEvents::IMPORT_FAILURE, $event);
        if ($event->hasException()) {
          throw $event->getException();
        }
      }
    }
  }

  /**
   * Checks dependencies of a CDF entry to determine if it can be processed.
   *
   * CDF entries are turned into Drupal entities. This can only be done when
   * all the dependencies of an entry have been created. This method checks
   * dependencies to ensure they've been properly converted into Drupal
   * entities before proceeding with processing an entry.
   *
   * @param \Acquia\ContentHubClient\CDF\CDFObject $object
   *   The CDF Object.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   *
   * @return bool
   *   Whether a CDF entry is processable or is not.
   */
  protected function entityIsProcessable(CDFObject $object, DependencyStack $stack) {
    foreach (array_keys($object->getDependencies()) as $dependency_uuid) {
      if (!$stack->hasDependency($dependency_uuid)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Ensures all required modules of a set of entities are enabled.
   *
   * If modules are missing from the code base, this method will throw an
   * exception before any importing of content can occur which should prevent
   * entities from being in half-operational states.
   *
   * @param \Acquia\ContentHubClient\CDFDocument $cdf
   *   The CDF Document.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   *
   * @throws \Exception
   *   The exception thrown if a module is missing from the code base.
   */
  protected function handleModules(CDFDocument $cdf, DependencyStack $stack) {
    $dependencies = [];
    $unordered_entities = $cdf->getEntities();

    foreach ($unordered_entities as &$entity) {
      // Don't process entities, their dependencies are working.
      if ($stack->hasDependency($entity->getUuid())) {
        continue;
      }
      // Don't process non-entities we've previously processed.
      if ($entity->hasProcessedDependencies()) {
        continue;
      }
      // No need to process entities that don't have module dependencies.
      if (!$entity->getModuleDependencies()) {
        continue;
      }
      $dependencies = NestedArray::mergeDeep($dependencies, $entity->getModuleDependencies());
      $entity->markProcessedDependencies();
    }

    foreach ($dependencies as $index => $module) {
      // @todo consider a configuration that prevents new module installation.
      // Module isn't installed.
      if (!$this->getModuleHandler()->moduleExists($module)) {
        // Module doesn't exist in the code base, so we can't install.
        if (!drupal_get_filename('module', $module)) {
          throw new \Exception(sprintf("The %s module code base is not present.", $module));
        }
      }
      else {
        unset($dependencies[$index]);
      }
    }

    if (!empty($dependencies)) {
      $this->moduleInstaller->install(array_values($dependencies));
    }

    unset($unordered_entities, $dependencies);
    // @todo determine if this cache invalidation is necessary.
    \Drupal::cache()->invalidateAll();
    // Using \Drupal::entityTypeManager() do to caching of the instance in
    // some services. Looks like a core bug.
    \Drupal::entityTypeManager()->clearCachedDefinitions();
  }

  /**
   * Get the module handler statically to prevent issues with module install.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler.
   */
  protected function getModuleHandler() {
    return \Drupal::moduleHandler();
  }

}
