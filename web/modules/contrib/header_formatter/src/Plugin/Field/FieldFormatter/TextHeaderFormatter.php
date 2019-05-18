<?php

namespace Drupal\header_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'text_header' formatter.
 *
 * @FieldFormatter(
 *   id = "text_header",
 *   label = @Translation("Header"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class TextHeaderFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
        'level' => 2,
      ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if (!parent::isApplicable($field_definition)) {
      return FALSE;
    }

    $cardinality = $field_definition->getFieldStorageDefinition()
      ->getCardinality();

    return $cardinality === 1;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => "h{$this->getSetting('level')}",
        '#value' => $item->value,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settingsForm = parent::settingsForm($form, $form_state);

    $settingsForm['level'] = [
      '#type' => 'select',
      '#options' => [
        2 => 'H2',
        3 => 'H3',
        4 => 'H4',
        5 => 'H5',
        6 => 'H6',
      ],
    ];

    return $settingsForm;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = "Header level: H{$this->getSetting('level')}";

    return $summary;
  }

}
