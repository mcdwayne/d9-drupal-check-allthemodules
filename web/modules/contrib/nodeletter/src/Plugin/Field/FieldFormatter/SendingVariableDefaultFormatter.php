<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\Field\FieldFormatter\SendingVariableDefaultFormatter.
 */

namespace  Drupal\nodeletter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Random_default' formatter.
 *
 * @FieldFormatter(
 *   id = "nodeletter_sending_variable_default",
 *   label = @Translation("Sending variable"),
 *   field_types = {
 *     "nodeletter_sending_variable"
 *   }
 * )
 */
class SendingVariableDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = t('Displays the variable value.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = array(
        '#type' => 'markup',
        '#markup' => $item->value,
      );
    }

    return $element;
  }
}
