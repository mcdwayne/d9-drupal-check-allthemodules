<?php

namespace Drupal\affiliates_connect\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\Plugin\Tamper\Implode as TamperImplode;

/**
 * Plugin implementation of the explode plugin.
 *
 * @Tamper(
 *   id = "affiliates_connect_implode",
 *   label = @Translation("Implode"),
 *   description = @Translation("Converts an array to a string."),
 *   category = "Affiliates Connect",
 *   handle_multiples = TRUE
 * )
 */
class Implode extends TamperImplode {

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_array($data)) {
      return '';
    }
    $glue = str_replace(['%s', '%t', '%n'], [' ', "\t", "\n"], $this->getSetting(self::SETTING_GLUE));
    return implode($glue, $data);
  }

}
