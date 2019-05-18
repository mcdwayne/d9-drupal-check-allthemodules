<?php

namespace Drupal\file_attributes\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'file_attributes' formatter.
 *
 * @FieldFormatter(
 *   id = "file_attributes",
 *   label = @Translation("File attributes"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileAttributesFieldFormatter extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $elements[$delta]['#options'] = $item->options;
      $elements[$delta]['#theme'] = 'file_attributes_link';
    }

    return $elements;
  }
}
