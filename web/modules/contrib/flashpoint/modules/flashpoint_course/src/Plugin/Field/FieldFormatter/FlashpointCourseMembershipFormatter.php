<?php

namespace Drupal\flashpoint_course\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flashpoint\FlashpointUtilities;

/**
 * Plugin implementation of the 'flashpoint_course_membership_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "flashpoint_course_membership_formatter",
 *   label = @Translation("Flashpoint course membership formatter"),
 *   field_types = {
 *     "flashpoint_course_membership_type"
 *   }
 * )
 */
class FlashpointCourseMembershipFormatter extends FormatterBase {

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
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
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
    $options = FlashpointUtilities::getOptions('course');
    $val = $item->value;
    if (isset($options[$val])) {
      return nl2br(Html::escape($options[$val]));
    }
    else {
      return '';
    }
  }

}
