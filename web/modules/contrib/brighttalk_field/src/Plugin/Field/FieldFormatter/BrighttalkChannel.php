<?php

namespace Drupal\brighttalk_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Channel' formatter.
 *
 * @FieldFormatter(
 *  id = "brighttalk_channel",
 *  label = @Translation("Channel"),
 *  field_types = {"brighttalk_channel"}
 * )
 */
class BrighttalkChannel extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary  = [];
    $summary[] = t('Displays a webcast.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      // Render each element as markup.
      $elements[$delta] = [
        '#theme'      => 'brighttalk_field_player',
        '#channel_id' => $item->channel_id,
        '#webcast_id' => 0,
      ];
    }

    return $elements;
  }

}
