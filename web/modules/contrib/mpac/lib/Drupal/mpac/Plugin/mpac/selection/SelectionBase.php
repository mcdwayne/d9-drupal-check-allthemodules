<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\mpac\selection\SelectionBase.
 */

namespace Drupal\mpac\Plugin\mpac\selection;

use Drupal\mpac\Annotation\MpacSelection;
use Drupal\Core\Annotation\Translation;
use Drupal\mpac\Plugin\Type\Selection\SelectionInterface;

/**
 * Plugin implementation of the 'selection' mpac.
 *
 * @MpacSelection(
 *   id = "default",
 *   module = "mpac",
 *   label = @Translation("Default"),
 *   group = "default",
 *   weight = 0,
 *   derivative = "Drupal\mpac\Plugin\Derivative\SelectionBase"
 * )
 */
class SelectionBase implements SelectionInterface {

  /**
   * Constructs a SelectionBase object.
   */
  public function __construct() {
  }

  public function countMatchingItems($match = NULL, $match_operator = 'CONTAINS') {
    return 0;
  }

  public function getMatchingItems($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return array();
  }

}
