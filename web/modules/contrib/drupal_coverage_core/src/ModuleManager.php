<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_coverage_core\Exception\ModuleDoesNotExistException;
use Drupal\node\Entity\Node;

/**
 * @todo Dynamically gather the branches.
 * @todo Introduce queue
 */

/**
 * Manages module content types.
 */
class ModuleManager {
  /**
   * The analysis manager.
   *
   * @var AnalysisManager
   */
  protected $analysisManager;

  /**
   * The storage used for this object.
   *
   * @var ModuleManagerStorage
   */
  protected $moduleManagerStorage;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  const TYPE_CORE = "Core";
  const TYPE_CONTRIB = "Contrib";

  /**
   * Constructs a ModuleManager.
   *
   * @param AnalysisManager $analysis_manager
   *   The analysis manager.
   * @param ModuleManagerStorageInterface $module_manager_storage
   *   The storage used for this object.
   */
  public function __construct(AnalysisManager $analysis_manager, ModuleManagerStorageInterface $module_manager_storage, ConfigFactoryInterface $config_factory) {
    $this->analysisManager = $analysis_manager;
    $this->moduleManagerStorage = $module_manager_storage;
    $this->configFactory = $config_factory;
  }

  /**
   * List the analyses that are currently being build.
   *
   * @return array|int
   *   A list of ids of modules.
   */
  public function getModules() {
    return $this->moduleManagerStorage->getModules();
  }

  /**
   * Get all analyses of a module.
   *
   * @param \Drupal\Core\Entity\EntityInterface $module
   *   The module.
   *
   * @return array|int|mixed
   *   An array containing all the ids of the analyses.
   */
  public function getAnalyses(EntityInterface $module) {
    return $this->moduleManagerStorage->getAnalyses($module);
  }

  /**
   * Get the last analysis of a given module.
   *
   * @param EntityInterface $module
   *   The module.
   *
   * @return EntityInterface
   *   The analysis.
   */
  public function getLastAnalysis(EntityInterface $module) {
    return $this->moduleManagerStorage->getLastAnalysis($module);
  }

  /**
   * Get the title of a module.
   *
   * @param EntityInterface $module
   *   The module.
   *
   * @return string
   *   The title of the module.
   */
  public function getTitle(EntityInterface $module) {
    return $module->title->getString();
  }

  /**
   * Creates a new core module.
   *
   * @param string $name
   *   The name of the module.
   *
   * @return EntityInterface
   *   The created module.
   */
  protected function createCoreModule($name, $test_group) {
    $data = [
      'type' => 'module',
      'title' => $name,
      'field_testcase' => $test_group,
    ];

    $module = Node::create($data);
    $module->save();

    return $module;
  }

  /**
   * Get a core module.
   *
   * @param string $title
   *   The name of the module.
   *
   * @return EntityInterface
   *   The module.
   */
  public function getCoreModule($title) {
    try {
      return $this->moduleManagerStorage->getCoreModule($title);
    }
    catch (ModuleDoesNotExistException $e) {
      $modules = $this->configFactory
        ->get('drupal_coverage_core.settings')
        ->get('modules')['drupal7'];

      $test_groups = $modules[self::cleanModuleName($title)]['test_group'];
      return $this->createCoreModule($title, $test_groups);
    }
  }

  /**
   * Creates a machine name for a module.
   *
   * @param string $module_name
   *   The full name of a module.
   * @param string $seperator
   *   The seperator which will be used for replacing spaces.
   *
   * @return string
   *   The machine name of the given module name.
   */
  public static function cleanModuleName($module_name, $seperator = "-") {
    return strtolower(str_replace(' ', $seperator, $module_name));
  }

}
