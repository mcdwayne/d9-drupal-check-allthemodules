<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

use Drupal\formatter_suite\Utilities;

/**
 * Presents an integer as a labeled horizontal bar of varying length.
 *
 * Settings select the bar's color, the background color behind the bar,
 * the bar's full length, and the bar's width. Optionally, the numeric
 * value may be shown before or after the bar using the parent class's
 * formatting.
 *
 * The bar is drawn by creating a 1-pixel data URL of the chosen bar
 * color.
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_number_with_bar_indicator",
 *   label       = @Translation("Formatter Suite - General number with bar indicator"),
 *   weight      = 1003,
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *   }
 * )
 */
class GeneralNumberWithBarIndicatorFormatter extends GeneralNumberFormatter {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of value locations.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getValueLocations() {
    return [
      'none'  => t('No value'),
      'left'  => t('Before bar'),
      'right' => t('After bar'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'barLength'       => '200',
        'barWidth'        => '15',
        'barColor'        => '#000000',
        'backgroundColor' => '#ffffff',
        'valueLocation'   => 'right',
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $this->sanitizeSettings();

    // Get current settings.
    $barLength     = $this->getSetting('barLength');
    $barWidth      = $this->getSetting('barWidth');
    $valueLocation = $this->getSetting('valueLocation');

    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];

    // Sanitize & validate.
    $valueLocations = $this->getValueLocations();
    if (empty($valueLocation) === TRUE ||
        isset($valueLocations[$valueLocation]) === FALSE) {
      $valueLocation = 'none';
    }

    $disabledByMinMax = FALSE;
    $disabledByLength = FALSE;
    if (isset($min) === FALSE || isset($max) === FALSE) {
      $disabledByMinMax = TRUE;
    }
    elseif ($barLength <= 0 || $barWidth <= 0) {
      $disabledByLength = TRUE;
    }
    else {
      $barLength .= 'px';
      $barWidth .= 'px';
    }

    // Summarize.
    $summary = [];
    if ($disabledByMinMax === TRUE) {
      $summary[] = $this->t('Disabled color bar, field min/max need to be set.');
      $summary = array_merge($summary, parent::settingsSummary());
    }
    elseif ($disabledByLength === TRUE) {
      $summary[] = $this->t('Disabled color bar, bar size needs to be set.');
      $summary = array_merge($summary, parent::settingsSummary());
    }
    else {
      $summary[] = $this->t(
        'Colored bar @barLength long, @barWidth wide.',
        [
          '@barLength'    => $barLength,
          '@barWidth' => $barWidth,
        ]);
      if ($valueLocation === 'none') {
        $summary[] = $this->t('No value shown.');
      }
      else {
        $summary[] = $this->t(
          'Value @location.',
          [
            '@location' => $valueLocations[$valueLocation],
          ]);
        $summary = array_merge($summary, parent::settingsSummary());
      }
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
    return $this->t('Draw a horizontal bar with a length based on the field value.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    // Get the parent's form, which includes a lot of settings for
    // formatting numbers.
    $elements = parent::settingsForm($form, $formState);

    // Add warning if min/max are not set.
    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];

    $disabled = FALSE;
    if (isset($min) === FALSE ||
        isset($max) === FALSE) {
      $disabled = TRUE;
      $elements['warning'] = [
        '#type'          => 'html_tag',
        '#tag'           => 'div',
        '#value'         => $this->t("To enable horizontal bar display, first set the minimum and maximum in the field's definition."),
        '#weight'        => -999,
        '#attributes'    => [
          'class'        => [
            'formatter_suite-settings-warning',
          ],
        ],
      ];
    }

    $weight = 100;

    // Prompt for each setting.
    $elements['sectionBreak'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    $elements['barLength'] = [
      '#title'         => $this->t('Max bar length'),
      '#type'          => 'number',
      '#min'           => 1,
      '#max'           => 5000,
      '#size'          => 5,
      '#default_value' => $this->getSetting('barLength'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-bar-indicator-bar-length',
        ],
      ],
    ];
    $elements['barWidth'] = [
      '#title'         => $this->t('Bar width'),
      '#type'          => 'number',
      '#min'           => 1,
      '#max'           => 5000,
      '#size'          => 5,
      '#default_value' => $this->getSetting('barWidth'),
      '#description'   => $this->t('Bar length and width in pixels.'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-bar-indicator-bar-width',
        ],
      ],
    ];
    $elements['barColor'] = [
      '#title'         => $this->t('Bar color'),
      '#type'          => 'textfield',
      '#size'          => 7,
      '#default_value' => $this->getSetting('barColor'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-bar-indicator-bar-color',
        ],
      ],
    ];
    $elements['backgroundColor'] = [
      '#title'         => $this->t('Background color'),
      '#type'          => 'textfield',
      '#size'          => 7,
      '#default_value' => $this->getSetting('backgroundColor'),
      '#description'   => $this->t("Colors use CSS syntax (e.g. '#ff0000'). Empty background uses page's background."),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-bar-indicator-background-color',
        ],
      ],
    ];
    $elements['valueLocation'] = [
      '#title'         => $this->t('Value location'),
      '#type'          => 'select',
      '#options'       => $this->getValueLocations(),
      '#default_value' => $this->getSetting('valueLocation'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-bar-indicator-value-location',
        ],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeSettings() {
    // Get settings.
    $barLength       = $this->getSetting('barLength');
    $barWidth        = $this->getSetting('barWidth');
    $barColor        = $this->getSetting('barColor');
    $backgroundColor = $this->getSetting('backgroundColor');
    $valueLocation   = $this->getSetting('valueLocation');
    $defaults        = $this->defaultSettings();

    // Sanitize & validate.
    parent::sanitizeSettings();

    $valueLocations = $this->getValueLocations();
    if (empty($valueLocation) === TRUE ||
        isset($valueLocations[$valueLocation]) === FALSE) {
      $valueLocation = $defaults['valueLocation'];
    }

    // Security: The bar length and weight have been entered by an
    // administrator. They both should be simple integers and should
    // not contain HTML or HTML entities.
    //
    // Parsing the values as integers ignores anything extra that
    // might be included in the value, such as spurious HTML.
    if (empty($barLength) === TRUE) {
      $barLength = intval($defaults['barLength']);
    }
    else {
      $barLength = intval($barLength);
      if ($barLength < 0) {
        $barLength = intval($defaults['barLength']);
      }
    }

    if (empty($barWidth) === TRUE) {
      $barWidth = intval($defaults['barWidth']);
    }
    else {
      $barWidth = intval($barWidth);
      if ($barWidth < 0) {
        $barWidth = intval($defaults['barWidth']);
      }
    }

    // Security: The bar and background colors have been entered by an
    // administrator. They both should be valid CSS colors of the form
    // #HEX.
    //
    // If a color doesn't start with '#', then it is illegal and we
    // revert to a default. Otherwise the color is escaped. The bar
    // color will be used to create an image, which will parse the
    // color. The background color will be included as an HTML attribute.
    if (empty($barColor) === TRUE || $barColor[0] !== '#') {
      $barColor = $defaults['barColor'];
    }
    else {
      $barColor = Html::escape($barColor);
    }

    if (empty($backgroundColor) === TRUE || $backgroundColor[0] !== '#') {
      $backgroundColor = $defaults['backgroundColor'];
    }
    else {
      $backgroundColor = Html::escape($backgroundColor);
    }

    $this->setSetting('barLength', $barLength);
    $this->setSetting('barWidth', $barWidth);
    $this->setSetting('barColor', $barColor);
    $this->setSetting('backgroundColor', $backgroundColor);
    $this->setSetting('valueLocation', $valueLocation);
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    // The $items array has a list of items to format. We need to return
    // an array with identical indexing and corresponding render elements
    // for those items.
    if (empty($items) === TRUE) {
      return [];
    }

    // Let the parent format all of the numbers first. This insures that
    // we have the parent's numeric formatting logic, including the use
    // of prefix and suffix, thousands marker, etc.
    //
    // This also sanitizes all settings.
    $build = parent::viewElements($items, $langCode);

    // Get current settings.
    $barColor        = $this->getSetting('barColor');
    $barLength       = $this->getSetting('barLength');
    $barWidth        = $this->getSetting('barWidth');
    $backgroundColor = $this->getSetting('backgroundColor');
    $valueLocation   = $this->getSetting('valueLocation');

    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];

    if (empty($barLength) === TRUE ||
        empty($barWidth) === TRUE ||
        isset($min) === FALSE ||
        isset($max) === FALSE) {
      // Missing bar size or field min/max needed to calculate bar length.
      // Disable bar. Just use the parent's formatting.
      return $build;
    }

    $barLength .= 'px';
    $barWidth .= 'px';

    // Create 1-pixel colored image.
    $imageData = Utilities::createImage($barColor);

    // Compute value range for converting values to percentages.
    $valueRange = ((float) $max - (float) $min);

    // Create the bar, optionally with a value label.
    foreach ($items as $delta => $item) {
      // Calculate a percentage.
      $percent = ((100.0 * ((float) $item->value - $min)) / $valueRange);

      // Get the value label, if any.
      $valueLabel = '';
      if ($valueLocation !== 'none') {
        // Security: The numeric value to show beside the bar has already
        // been formatted by the parent class. It is known to be markup
        // so there is no further need to check it.
        //
        // Below, the value will be again added as markup, either before
        // or after the bar.
        $valueLabel = $build[$delta]['#markup'];
      }

      unset($build[$delta]['#markup']);

      // Create a container to include the label and bar.
      $build[$delta] = [
        '#type'         => 'container',
        '#attributes'   => [
          'class'       => [
            'formatter_suite-general-number-with-bar-indicator-wrapper',
          ],
        ],
        '#attached'     => [
          'library'     => [
            'formatter_suite/formatter_suite.usage',
          ],
        ],
      ];

      // If the value label goes first, add it.
      if ($valueLocation === 'left') {
        $build[$delta]['value'] = [
          '#markup' => $valueLabel,
        ];
      }

      // Add the bar.
      //
      // Use inline styles because:
      // * The bar area length and width are parameters.
      // * The bar and background colors are parameters.
      $backgroundStyle = "width: $barLength; height: $barWidth;";
      if (empty($backgroundColor) === FALSE) {
        $backgroundStyle .= "background-color: $backgroundColor;";
      }

      $build[$delta]['barouter'] = [
        '#type'         => 'container',
        '#attributes'   => [
          'class'       => [
            'formatter_suite-general-number-with-bar-indicator-outer',
            'formatter_suite-general-number-with-bar-indicator-' . $valueLocation,
          ],
          'style'       => $backgroundStyle,
        ],
        'bar'           => [
          '#type'       => 'html_tag',
          '#tag'        => 'img',
          '#attributes' => [
            'class'     => [
              'formatter_suite-general-number-with-bar-indicator',
            ],
            'src'       => $imageData,
            'width'     => "$percent%",
            'height'    => $barWidth,
          ],
        ],
      ];

      // If the value label goes last, add it.
      if ($valueLocation === 'right') {
        $build[$delta]['value'] = [
          '#markup'     => $valueLabel,
        ];
      }
    }

    return $build;
  }

}
