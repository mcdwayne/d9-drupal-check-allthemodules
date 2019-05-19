<?php

/**
 * @file
 * Helper class for WoW unit test base classes.
 */

namespace Drupal\wow\Tests;

/**
 * Defines UnitTestBase class test.
 */
class UnitTestBase extends \DrupalUnitTestCase {

  protected $entityInfos;

  protected function setUp() {
    // Registry does not exists in a Unit Test context.
    spl_autoload_unregister('drupal_autoload_class');
    spl_autoload_unregister('drupal_autoload_interface');
    drupal_load('module', 'classloader');
    $this->registerNamespace('WoW\Core', 'wow');
    $this->registerNamespace('Drupal\wow', 'wow');

    // Load the Entity base class.
    module_load_include('inc', 'entity', 'includes/entity');
    module_load_include('inc', 'entity', 'includes/entity.controller');

    $this->entityInfos = &drupal_static('entity_get_info', array());
    parent::setUp();
  }

  protected function registerNamespace($namespace, $module) {
    $loader = drupal_classloader();
    $loader->registerNamespace($namespace, DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . '/lib');
  }
}
