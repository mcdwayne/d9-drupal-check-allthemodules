<?php

/**
 * @file
 * Contains \Drupal\probabilistic_weight\Plugin\field\formatter\ProbabilisticWeightTextFormatter.
 */

namespace Drupal\probabilistic_weight\Plugin\field\formatter;

use Drupal\field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin implementation of the 'probabilistic_weight_text' formatter.
 *
 * @FieldFormatter(
 *   id = "probabilistic_weight_text",
 *   module = "probabilistic_weight",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "probabilistic_weight"
 *   }
 * )
 */
class ProbabilisticWeightTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, array $items) {
    $element = array();
    foreach ($items as $delta => $item) {
      // Render each element as text.
      $element[$delta] = array(
        '#type' => 'markup',
        '#markup' => $item['weight'],
      );
    }
    return $element;
  }

}
