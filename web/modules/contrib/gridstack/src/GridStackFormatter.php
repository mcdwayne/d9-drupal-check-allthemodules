<?php

namespace Drupal\gridstack;

use Drupal\gridstack\Entity\GridStack;
use Drupal\blazy\BlazyFormatterManager;

/**
 * Implements GridStackFormatterInterface.
 */
class GridStackFormatter extends BlazyFormatterManager implements GridStackFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Prepare integration with Blazy.
    $settings['item_id']   = 'box';
    $settings['namespace'] = 'gridstack';
    $settings['ratio']     = 'fluid';
    $settings['blazy']     = TRUE;
    $settings['lazy']      = 'blazy';

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    parent::preBuildElements($build, $items, $entities);

    $settings = &$build['settings'];

    // GridStack specific stuffs.
    $build['optionset'] = GridStack::loadWithFallback($settings['optionset']);

    // Converts gridstack breakpoint grids from stored JSON into array.
    unset($settings['breakpoints']);
    $build['optionset']->gridsJsonToArray($settings);

    $this->getModuleHandler()->alter('gridstack_settings', $build, $items);
  }

}
