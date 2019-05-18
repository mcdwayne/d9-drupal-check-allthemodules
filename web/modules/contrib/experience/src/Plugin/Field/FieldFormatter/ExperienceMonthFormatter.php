<?php

namespace Drupal\experience\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Month' formatter for 'experience' fields.
 *
 * @FieldFormatter(
 *   id = "experience_month",
 *   label = @Translation("Month"),
 *   field_types = {
 *     "experience"
 *   }
 * )
 */
class ExperienceMonthFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      // Render each element as experience.
      if (empty($item->value)) {
        if ($item->value == 0) {
          $element[$delta] = [
            '#markup' => $this->t('Fresher'),
          ];
        }
      }
      elseif (!empty($item->value)) {
        $element[$delta] = [
          '#markup' => $this->t('@month Month(s)', ['@month' => $item->value]),
        ];
      }

      if (!empty($item->_attributes)) {
        $element[$delta]['#options'] += ['attributes' => []];
        $element[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }

}
