<?php

namespace Drupal\advanded_number_format\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\FloatFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'advanded_number_format' formatter.
 *
 * @FieldFormatter(
 *   id = "advanded_number_format_float",
 *   label = @Translation("Advanced number format"),
 *   field_types = {
 *     "float"
 *   }
 * )
 */
class AdvancedNumberFormatFloatFormatter extends FloatFormatter {

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
    $summary = parent::settingsSummary();
    // Implement settings summary.

    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    $scale = $this->getSetting('scale');

    // Only show existing digits and maximum $scale digits. Remove all trailing zeros.
    $digits = explode('.', $number + 0);
    if (empty($digits[1])) {
      $scale = 0;
    }
    elseif (strlen($digits[1]) < $scale) {
      $scale = strlen($digits[1]);
    }
    return number_format($number, $scale, $this->getSetting('decimal_separator'), $this->getSetting('thousand_separator'));
  }

}
