<?php

namespace Drupal\human_decimal\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'human_decimal' formatter.
 *
 * @FieldFormatter(
 *   id = "human_decimal",
 *   label = @Translation("Human decimal"),
 *   field_types = {
 *     "decimal"
 *   }
 * )
 */
class HumanDecimal extends DecimalFormatter {

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
