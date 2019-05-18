<?php

/**
 * @file
 * Contains \Drupal\link_popup\Plugin\Field\FieldFormatter\LinkPopupFieldFormatter.
 */

namespace Drupal\link_popup\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'link_popup_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "link_popup_field_formatter",
 *   label = @Translation("Link popup field formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkPopupFieldFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    $item_value = $item->getValue();
    $url = Url::fromUri($item_value['uri']);
    $conf = \Drupal::config('link_popup.linkpopupsettings');
    $width = $conf->get('width');
    return [
      '#theme' => 'link_popup_formatter',
      '#attributes' => 'link_popup',
      '#link_text' => Html::escape($item_value['title']),
      '#link_url' => $url,
      '#width' => $width,
    ];
  }

}