<?php
/**
 * @file
 * Contains
 */

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_coverage_core\Exception\InvalidModuleTypeException;
use Drupal\node\Entity\Node;

class BuildData {

  /**
   * @var Node
   */
  protected $module;

  /**
   * @var string
   */
  protected $branch;

  /**
   * @var \stdClass
   */
  protected $buildData;

  /**
   * @var string
   */
  protected $module_type;

  /**
   * @return string
   */
  public function getModuleType() {
    return $this->module_type;
  }

  /**
   * @param string $module_type
   */
  public function setModuleType($module_type) {

    if ($module_type !== ModuleManager::TYPE_CORE && $module_type !== ModuleManager::TYPE_CONTRIB) {
      throw new InvalidModuleTypeException();
    }
    else {
      $this->module_type = $module_type;
    }
  }

  /**
   * @return mixed
   */
  public function getBuildData() {
    return $this->buildData;
  }

  /**
   * @param mixed $build_data
   */
  public function setBuildData($build_data) {
    $this->buildData = $build_data;
  }

  /**
   * @return mixed
   */
  public function getBranch() {
    return $this->branch;
  }

  /**
   * @param mixed $branchId
   */
  public function setBranch($branch) {
    $this->branch = $branch;
  }

  /**
   * @return Node
   */
  public function getModule() {
    return $this->module;
  }

  /**
   * @param EntityInterface $module
   */
  public function setModule($module) {
    $this->module = $module;
  }

  public function getBuildStatus() {
    $build_status = Generator::BUILD_BUILDING;
    $build_data = $this->getBuildData();

    if (is_object($build_data) && property_exists($build_data, 'state')) {
      switch ($build_data->state) {
        case "failed":
          $build_status = Generator::BUILD_FAILED;
          break;

        case "finished":
          $build_status = Generator::BUILD_SUCCESSFUL;
          break;
      }
    }

    return $build_status;
  }

}