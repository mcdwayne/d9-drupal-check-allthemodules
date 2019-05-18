<?php

namespace Drupal\layout_builder_restrictions\Traits;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;

/**
 * Methods to help Layout Builder Restrictions plugins.
 */
trait PluginHelperTrait {

  use LayoutBuilderContextTrait;

  /**
   * Gets block definitions appropriate for an entity display.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display
   *   The entity display being edited.
   *
   * @return array[]
   *   Keys are category names, and values are arrays of which the keys are
   *   plugin IDs and the values are plugin definitions.
   */
  protected function getBlockDefinitions(LayoutEntityDisplayInterface $display) {

    // Check for 'load' method, which only exists in > 8.7.
    if (method_exists($this->sectionStorageManager(), 'load')) {
      $section_storage = $this->sectionStorageManager()->load('defaults', ['display' => EntityContext::fromEntity($display)]);
    }
    else {
      // BC for < 8.7.
      $section_storage = $this->sectionStorageManager()->loadEmpty('defaults')->setSectionList($display);
    }
    // Do not use the plugin filterer here, but still filter by contexts.
    $definitions = $this->blockManager()->getDefinitions();
    $definitions = $this->contextHandler()->filterPluginDefinitionsByContexts($this->getAvailableContexts($section_storage), $definitions);
    return $this->blockManager()->getGroupedDefinitions($definitions);
  }

  /**
   * Gets layout definitions.
   *
   * @return array[]
   *   Keys are layout machine names, and values are layout definitions.
   */
  protected function getLayoutDefinitions() {
    return $this->layoutManager()->getFilteredDefinitions('layout_builder', []);
  }

  /**
   * Gets the section storage manager.
   *
   * @return \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   *   The section storage manager.
   */
  private function sectionStorageManager() {
    return $this->sectionStorageManager ?: \Drupal::service('plugin.manager.layout_builder.section_storage');
  }

  /**
   * Gets the block manager.
   *
   * @return \Drupal\Core\Block\BlockManagerInterface
   *   The block manager.
   */
  private function blockManager() {
    return $this->blockManager ?: \Drupal::service('plugin.manager.block');
  }

  /**
   * Gets the layout plugin manager.
   *
   * @return \Drupal\Core\Layout\LayoutPluginManagerInterface
   *   The layout plugin manager.
   */
  private function layoutManager() {
    return $this->layoutManager ?: \Drupal::service('plugin.manager.core.layout');
  }

  /**
   * Gets the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   *   The context handler.
   */
  private function contextHandler() {
    return $this->contextHandler ?: \Drupal::service('context.handler');
  }

}
