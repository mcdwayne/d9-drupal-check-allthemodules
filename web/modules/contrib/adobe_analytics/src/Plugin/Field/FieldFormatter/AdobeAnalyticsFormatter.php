<?php

namespace Drupal\adobe_analytics\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Adobe Analytics' formatter.
 *
 * @FieldFormatter(
 *   id = "adobe_analytics",
 *   label = @Translation("Adobe Analytics"),
 *   field_types = {
 *     "adobe_analytics"
 *   }
 * )
 */
class AdobeAnalyticsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

}
