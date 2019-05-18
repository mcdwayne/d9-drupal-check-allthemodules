<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\formatter_suite\Branding;
use Drupal\formatter_suite\Utilities;

/**
 * Formats numbers with a byte suffix, like "bytes", "KB", or "MB".
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_number_with_bytes",
 *   label       = @Translation("Formatter Suite - Bytes with KB/MB/GB suffix"),
 *   weight      = 900,
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float",
 *   }
 * )
 */
class NumberWithBytesFormatter extends FormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of "k" units.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getKiloUnits() {
    return [
      1000 => t('Kilobytes, Megabytes, Gigabytes, etc.'),
      1024 => t('Kibibytes, Mibibytes, Gibibytes, etc.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'kunit'         => 1000,
        'fullWord'      => FALSE,
        'decimalDigits' => 2,
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $this->sanitizeSettings();

    $summary = [];
    $summary[] = $this->t(
      'Sample: @value',
      [
        '@value' => Utilities::formatBytes(
          1289748,
          $this->getSetting('kunit'),
          $this->getSetting('fullWord'),
          $this->getSetting('decimalDigits')),
      ]);

    switch ($this->getSetting('kunit')) {
      default:
      case 1000:
        if ($this->getSetting('fullWord') === FALSE) {
          $summary[] = $this->t('KB, MB, GB, etc.');
        }
        else {
          $summary[] = $this->t('Kilobyte, Megabyte, Gigabyte, etc.');
        }
        break;

      case 1024:
        if ($this->getSetting('fullWord') === FALSE) {
          $summary[] = $this->t('KiB, MiB, GiB, etc.');
        }
        else {
          $summary[] = $this->t('Kibibyte, Mebibyte, Gibibyte, etc.');
        }
        break;
    }

    return $summary;
  }

  /*---------------------------------------------------------------------
   *
   * Settings form.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t("Formats a number as a quantity of bytes, simplifying the number and appending the appropriate suffix. Quantities can be reported in international standard <em>Kilobytes</em> (1000 bytes = 1 KB) or legacy <em>Kibibytes</em> (1024 bytes = 1 KiB).");
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    // Get the parent's form.
    $elements = parent::settingsForm($form, $formState);

    // Add branding.
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'formatter_suite/formatter_suite.fieldformatter';

    // Add description.
    //
    // Use a large negative weight to insure it comes first.
    $elements['description'] = [
      '#type'          => 'html_tag',
      '#tag'           => 'div',
      '#value'         => $this->getDescription(),
      '#weight'        => -1000,
      '#attributes'    => [
        'class'        => [
          'formatter_suite-settings-description',
        ],
      ],
    ];

    $weight = 0;

    // Prompt for each setting.
    $elements['kunit'] = [
      '#title'         => $this->t('Bytes units'),
      '#type'          => 'select',
      '#options'       => $this->getKiloUnits(),
      '#default_value' => $this->getSetting('kunit'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-number-with-bytes-kunit',
        ],
      ],
    ];

    $elements['fullWord'] = [
      '#title'         => $this->t('Use full words, not abbreviations (e.g. "Kilobyte" vs. "KB")'),
      '#type'          => 'checkbox',
      '#default_value' => $this->getSetting('fullWord'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-number-with-bytes-full-word',
        ],
      ],
    ];

    $elements['decimalDigits'] = [
      '#title'         => $this->t('Decimal digits'),
      '#type'          => 'number',
      '#min'           => 0,
      '#max'           => 3,
      '#default_value' => $this->getSetting('decimalDigits'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-number-with-bytes-decimal-digits',
        ],
      ],
    ];

    return $elements;
  }

  /**
   * Sanitize settings to insure that they are safe and valid.
   *
   * @internal
   * Drupal's class hierarchy for plugins and their settings does not
   * include a 'validate' function, like that for other classes with forms.
   * Validation must therefore occur on use, rather than on form submission.
   * @endinternal
   */
  protected function sanitizeSettings() {
    // Get settings.
    $kunit         = $this->getSetting('kunit');
    $fullWord      = $this->getSetting('fullWord');
    $decimalDigits = $this->getSetting('decimalDigits');
    $defaults      = $this->defaultSettings();

    // Sanitize & validate.
    $kunits = $this->getKiloUnits();
    if (empty($kunit) === TRUE ||
        isset($kunits[$kunit]) === FALSE) {
      $kunit = $defaults['kunit'];
      $this->setSetting('kunit', $kunit);
    }

    $fullWord = boolval($fullWord);
    $this->setSetting('fullWord', $fullWord);

    $decimalDigits = intval($decimalDigits);
    if ($decimalDigits < 0) {
      $decimalDigits = 0;
    }
    elseif ($decimalDigits > 3) {
      $decimalDigits = 3;
    }

    $this->setSetting('decimalDigits', $decimalDigits);
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->sanitizeSettings();

    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => Utilities::formatBytes(
          $item->value,
          $this->getSetting('kunit'),
          $this->getSetting('fullWord'),
          $this->getSetting('decimalDigits')),
      ];
    }

    return $elements;
  }

}
