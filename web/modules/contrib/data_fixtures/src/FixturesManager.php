<?php

namespace Drupal\data_fixtures;

use Drupal\data_fixtures\Interfaces\Generator;

/**
 * Class FixturesManager.
 *
 * @package Drupal\data_fixtures
 */
class FixturesManager {

  /**
   * An unsorted array of arrays of active fixture generators.
   *
   * An associative array. The keys are integers that indicate priority. Values
   * are arrays of Generator objects.
   *
   * @var \Drupal\data_fixtures\Interfaces\Generator[][]
   *
   * @see \Drupal\data_fixtures\FixturesManager::addGenerator
   */
  private $generators = [];

  /**
   * An array of generators, sorted by priority.
   *
   * If this is NULL a rebuild will be triggered.
   *
   * @var null|\Drupal\data_fixtures\Interfaces\Generator[]
   *
   * @see \Drupal\data_fixtures\FixturesManager::addGenerator
   * @see \Drupal\data_fixtures\FixturesManager::sortGenerators
   */
  private $sortedGenerators = NULL;

  /**
   * Add a generator object to the current generators list.
   *
   * @param \Drupal\data_fixtures\Interfaces\Generator $generator
   *   Generator object instance.
   * @param int $priority
   *   Priority in the list.
   * @param string $alias
   *   Alias of the generator object.
   *
   * @return $this
   *
   * @throws \ReflectionException
   */
  public function addGenerator(Generator $generator, $priority = 0, $alias = NULL) {
    $this->generators[$priority][] = new FixturesGenerator($generator, $alias);
    // Trigger a rebuild of the sorted generators.
    $this->sortedGenerators = NULL;
    return $this;
  }

  /**
   * Return the generators array.
   *
   * @param bool $reverse
   *   Retrieve the generators in reverse order if set to true.
   *
   * @return \Drupal\data_fixtures\Interfaces\Generator[]
   *   Array of generator instances.
   */
  public function getGenerators($reverse = FALSE) {
    if ($this->sortedGenerators === NULL) {
      $this->sortedGenerators = $this->sortGenerators();
    }

    if ($reverse) {
      return array_reverse($this->sortedGenerators);
    }

    return $this->sortedGenerators;
  }

  /**
   * Loads all fixture generators.
   */
  public function loadGenerators() {
    foreach ($this->getGenerators() as $generator) {
      $generator->load();
    }
  }

  /**
   * Unloads all fixture generators.
   */
  public function unLoadGenerators() {
    foreach ($this->getGenerators() as $generator) {
      $generator->unLoad();
    }
  }

  /**
   * Sorts Generators according to priority.
   *
   * @return \Drupal\data_fixtures\Interfaces\Generator[]
   *   A sorted array of generator objects.
   */
  private function sortGenerators() {
    $sorted = [];
    ksort($this->generators);

    foreach ($this->generators as $generators) {
      $sorted = array_merge($sorted, $generators);
    }
    return $sorted;
  }

}
