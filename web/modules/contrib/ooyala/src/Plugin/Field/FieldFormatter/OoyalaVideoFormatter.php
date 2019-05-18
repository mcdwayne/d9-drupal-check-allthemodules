<?php

/**
 * @file
 * Contains \Drupal\ooyala\Plugin\Field\FieldFormatter\OoyalaVideoFormatter.
 */

namespace Drupal\ooyala\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'ooyala_video' formatter.
 *
 * @FieldFormatter(
 *   id = "ooyala_video_formatter",
 *   label = @Translation("Ooyala V4 Player"),
 *   field_types = {
 *     "ooyala_video"
 *   }
 * )
 */
class OoyalaVideoFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [$this->t('Renders the Ooyala player.')];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $manager = \Drupal::getContainer()->get('ooyala.ooyala_manager');

    foreach ($items as $delta => $item) {
      $elements[] = $manager->render($item);
    }

    return $elements;
  }
}
