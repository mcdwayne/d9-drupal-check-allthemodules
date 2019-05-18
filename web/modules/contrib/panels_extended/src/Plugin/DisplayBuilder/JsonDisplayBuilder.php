<?php

namespace Drupal\panels_extended\Plugin\DisplayBuilder;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_extended\BlockConfig\JsonOutputInterface;

/**
 * Provides a display builder which can also output to JSON.
 *
 * @DisplayBuilder(
 *   id = "panels_extended_json",
 *   label = @Translation("JSON")
 * )
 */
class JsonDisplayBuilder extends ExtendedDisplayBuilder {

  /**
   * {@inheritdoc}
   */
  public function build(PanelsDisplayVariant $panels_display) {
    if (!_panels_extended_is_json_requested()) {
      return parent::build($panels_display);
    }

    $regions = $panels_display->getRegionAssignments();
    $contexts = $panels_display->getContexts();

    $startTime = microtime(TRUE);
    $regions = $this->buildRegions($regions, $contexts);

    // Add the time in milliseconds it took to render the output.
    $regions['#renderTimeMs'] = (round(microtime(TRUE) - $startTime, 3) * 1000);
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRegions(array $regions, array $contexts) {
    if (!_panels_extended_is_json_requested()) {
      return parent::buildRegions($regions, $contexts);
    }

    $filteredRegions = $this->filterVisibleBlocks($regions);
    $this->dispatchPrebuildRegionsEvent($filteredRegions);

    $build = [];
    foreach ($filteredRegions as $region => $blocks) {
      $build[$region] = [];
      if (!$blocks) {
        continue;
      }

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block) {
        if ($block instanceof ContextAwarePluginInterface) {
          $this->contextHandler->applyContextMapping($block, $contexts);
        }

        if (!$block->access($this->account)) {
          continue;
        }

        $build[$region][] = $this->buildBlock($block);
      }
    }
    return $build;
  }

  /**
   * Builds a single block for outputting to JSON.
   *
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   The block to build.
   *
   * @return array
   *   The data for the JSON response.
   */
  protected function buildBlock(BlockPluginInterface $block) {
    if ($block instanceof JsonOutputInterface) {
      return $block->buildForJson();
    }
    return [
      '#configuration' => $block->getConfiguration(),
      'content' => $block->build(),
      'type' => $block->getPluginId(),
    ];
  }

}
