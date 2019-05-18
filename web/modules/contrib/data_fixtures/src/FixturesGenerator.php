<?php

namespace Drupal\data_fixtures;

use Drupal\data_fixtures\Interfaces\Generator;

/**
 * A wrapper around a Generator that gives access to some useful information.
 *
 * @package Drupal\data_fixtures
 */
class FixturesGenerator {

  /**
   * The (optional) alias of the Generator.
   *
   * @var string
   */
  private $alias;

  /**
   * The Generator instance.
   *
   * @var \Drupal\data_fixtures\Interfaces\Generator
   */
  private $generator;

  /**
   * FixturesGenerator constructor.
   *
   * @param \Drupal\data_fixtures\Interfaces\Generator $generator
   *   GThe Generator instance.
   * @param null|string $alias
   *   The (optional) alias of the Generator.
   *
   * @throws \ReflectionException
   */
  public function __construct(Generator $generator, $alias = NULL) {
    $this->setGenerator($generator);
    $this->setAlias($alias);
  }

  /**
   * Return a pretty identifier of the generator object.
   *
   * @return string
   *   Pretty identifier of the generator.
   */
  public function prettyPrint() {
    return $this->getAlias() . ' :: ' . $this->getClassName();
  }

  /**
   * Return the class name of the generator object.
   *
   * @return string
   *   Class name of the generator object.
   */
  public function getClassName() {
    return get_class($this->generator);
  }

  /**
   * Return the alias of the generator object.
   *
   * @return string
   *   Alias of the generator.
   */
  public function getAlias() {
    return $this->alias;
  }

  /**
   * Set the alias of the generator object.
   *
   * @param string $alias
   *   Alias of the generator.
   *
   * @throws \ReflectionException
   */
  public function setAlias($alias) {
    if (empty($alias)) {
      $alias = (new \ReflectionClass($this->getGenerator()))->getShortName();
    }

    $this->alias = $alias;
  }

  /**
   * Return the generator object.
   *
   * @return \Drupal\data_fixtures\Interfaces\Generator
   *   Generator object instance.
   */
  public function getGenerator() {
    return $this->generator;
  }

  /**
   * Set the generator object.
   *
   * @param \Drupal\data_fixtures\Interfaces\Generator $generator
   *   Generator object instance.
   */
  public function setGenerator(Generator $generator) {
    $this->generator = $generator;
  }

}
