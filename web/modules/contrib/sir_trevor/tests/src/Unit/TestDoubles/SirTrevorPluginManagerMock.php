<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

use Drupal\sir_trevor\Plugin\SirTrevorBlock;
use Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface;

class SirTrevorPluginManagerMock implements SirTrevorPluginManagerInterface {
  /** @var SirTrevorBlock[] */
  protected $instances = [];
  /** @var array */
  protected $definitions = [];
  /** @var \Drupal\sir_trevor\Plugin\SirTrevorPlugin [] */
  private $blocks = [];
  /** @var \Drupal\sir_trevor\Plugin\SirTrevorPlugin [] */
  private $enabledBlocks = [];

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDefinition($plugin_id) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // Intentionally left empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $requiredOptions = ['template', 'id'];
    foreach($requiredOptions as $option) {
      if (!isset($options[$option])) {
        $options[$option] = $option;
      }
    }
    // Set the required template argument if it isn't part of the definition.
    return new SirTrevorBlock($options);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefinitions($definitions) {
    $this->definitions = $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances() {
    return $this->instances;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstances(array $instances) {
    $this->instances = $instances;
  }

  /**
   * @param \Drupal\sir_trevor\Plugin\SirTrevorPlugin[] $blocks
   */
  public function setBlocks(array $blocks) {
    $this->blocks = $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocks() {
    return $this->blocks;
  }

  /**
   * @param \Drupal\sir_trevor\Plugin\SirTrevorPlugin[] $blocks
   */
  public function setEnabledBlocks(array $blocks) {
    $this->enabledBlocks = $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBlocks() {
    return $this->enabledBlocks;
  }
}
