<?php

namespace Drupal\experience\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Default' formatter for 'experience' fields.
 *
 * @FieldFormatter(
 *   id = "experience_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "experience"
 *   }
 * )
 */
class ExperienceDefaultFormatter extends FormatterBase {

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
        if ($item->value > 11) {
          $year = floor($item->value / 12);
          $month = $item->value % 12;
        }
        else {
          $year = 0;
          $month = $item->value;
        }
        if ($year && $month) {
          $element[$delta] = [
            '#markup' => $this->t('@year Year(s) @month Month(s)', ['@year' => $year, '@month' => $month]),
          ];
        }
        elseif ($year) {
          $element[$delta] = [
            '#markup' => $this->t('@year Year(s)', ['@year' => $year]),
          ];
        }
        elseif ($month) {
          $element[$delta] = [
            '#markup' => $this->t('@month Month(s)', ['@month' => $month]),
          ];
        }
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
