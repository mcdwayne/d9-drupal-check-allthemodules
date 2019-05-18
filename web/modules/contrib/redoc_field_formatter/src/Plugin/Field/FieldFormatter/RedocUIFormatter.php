<?php

namespace Drupal\redoc_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'redoc_ui' formatter.
 *
 * @FieldFormatter(
 *   id = "redoc_ui",
 *   label = @Translation("Redoc UI"),
 *   description = @Translation("Formats file fields with Redoc YAML or JSON
 *   files with a rendered Redoc UI"), field_types = {
 *     "file"
 *   }
 * )
 */
class RedocUIFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [

      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $elements['#attached']['library'][] = 'redoc_field_formatter/redoc_field_formatter.redoc';
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $redoc_file = file_create_url($file->getFileUri());
      $element[$delta] = [
        '#theme' => 'redoc_ui_field_item',
        '#field_name' => $this->fieldDefinition->getName(),
        '#delta' => $delta,
        '#file_url' => $redoc_file
      ];
    }
    return $element;
  }

}
