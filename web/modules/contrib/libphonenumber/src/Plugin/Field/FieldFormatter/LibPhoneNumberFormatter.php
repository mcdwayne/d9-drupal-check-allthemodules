<?php

namespace Drupal\libphonenumber\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Plugin implementation of the 'libphonenumber_link' formatter.
 *
 * @FieldFormatter(
 *   id = "libphonenumber_link",
 *   label = @Translation("Phone number link"),
 *   field_types = {
 *     "libphonenumber"
 *   }
 * )
 */
class LibPhoneNumberFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
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
      $elements[$delta] = $this->viewElement($item);
    }

    return $elements;
  }

  /**
   * Builds a render array for a single phone number item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The phone number item.
   *
   * @return array
   *   A renderable array.
   */
  protected function viewElement(FieldItemInterface $item) {
    /** @var \Drupal\libphonenumber\LibPhoneNumberInterface $item */
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    $util = PhoneNumberUtil::getInstance();
    // @todo Allow to set a default country other than Belgium.
    $number = $util->parseAndKeepRawInput($item->getRawInput(), 'BE');

    return [
      '#type' => 'link',
      // @todo Allow to choose the display format.
      '#title' => $util->format($number, PhoneNumberFormat::INTERNATIONAL),
      '#url' => Url::fromUri('tel:' . $util->format($number, PhoneNumberFormat::E164)),
    ];
  }

}
