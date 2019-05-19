<?php

namespace Drupal\Tests\sir_trevor\Unit\Plugin\TestDoubles;

use Drupal\Core\Extension\ModuleHandlerInterface;

class ModuleHandlerMock implements ModuleHandlerInterface {
  private $moduleDirectories;

  /**
   * {@inheritdoc}
   */
  public function load($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function isLoaded() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleList() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getModule($name) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function setModuleList(array $module_list = array()) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function addModule($name, $path) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function addProfile($name, $path) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function buildModuleDependencies(array $modules) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function moduleExists($module) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllIncludes($type, $name = NULL) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function loadInclude($module, $type, $name = NULL) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getHookInfo() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getImplementations($hook) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function writeCache() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function resetImplementations() {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function implementsHook($module, $hook) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function invoke($module, $hook, array $args = array()) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function invokeAll($hook, array $args = array()) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleDirectories() {
    return $this->moduleDirectories;
  }

  /**
   * {@inheritdoc}
   */
  public function getName($module) {
    // Intentionally left empty.
  }

  public function setModuleDirectories($directories) {
    $this->moduleDirectories = $directories;
  }
}
