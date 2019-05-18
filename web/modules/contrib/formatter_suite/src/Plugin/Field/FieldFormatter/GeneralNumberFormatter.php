<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Render\FormattableMarkup;

use Drupal\formatter_suite\Branding;

/**
 * Format a number field with a variety of notation styles and parameters.
 *
 * This class supports multiple notation styles for integer,
 * decimal, and floating-point values:
 *
 *   - "Basic number" notation is a simplified number with a few settings
 *     for the number of decimal places and whether to use a thousands
 *     separator (always a ',').
 *
 *   - "General number" notation adds lots of settings. It supports a number
 *     of decimal places, several positive and negative styles, a choice of
 *     thousands and decimal separators, and zero padding to a width.
 *
 *   - "Percentage" notation is a variant of "Basic number" for percentages.
 *     It always multiplies values by 100 and adds a '%' suffix. Otherwise
 *     it has the same settings as "Basic number".
 *
 *   - "Scientific" notation is for science data. It strives to conform to
 *     international standards. It supports two exponent styles for E-notation
 *     and superscript powers. It also supports a setting for the number of
 *     decimal places.
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_number",
 *   label       = @Translation("Formatter Suite - General number"),
 *   weight      = 1000,
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *   }
 * )
 */
class GeneralNumberFormatter extends FormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of notation styles.
   *
   * A notation style indicates a broad category of numeric presentation,
   * such as scientific notation vs. general number notation.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getNotationStyles() {
    return [
      'basicnumber'   => t('Basic number'),
      'generalnumber' => t('General number'),
      'numeralsystem' => t('Numeral system'),
      'percentage'    => t('Percentage'),
      'scientific'    => t('Scientific'),
    ];
  }

  /**
   * Returns an array of scientific notation exponent styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getExponentStyles() {
    return [
      'enotation'   => t('E-notation'),
      'superscript' => t('Superscript'),
    ];
  }

  /**
   * Returns an array of negative styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getNegativeStyles() {
    return [
      'minus'          => t('Minus sign'),
      'red'            => t('Red'),
      'parenthesis'    => t('Parenthesis'),
      'redparenthesis' => t('Red parenthesis'),
    ];
  }

  /**
   * Returns an array of positive styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getPositiveStyles() {
    return [
      'omit' => t('No plus sign'),
      'plus' => t('Plus sign'),
    ];
  }

  /**
   * Returns an array of thousands separators.
   *
   * @return string[]
   *   Returns an associative array with separator values as keys and
   *   human-readable translated names as values.
   */
  protected static function getThousandsSeparators() {
    return [
      ','       => t('Comma'),
      '.'       => t('Decimal point'),
      "'"       => t('Apostrophe'),
      ' '       => t('Space'),
      chr(8201) => t('Thin space'),
    ];
  }

  /**
   * Returns an array of decimal separators.
   *
   * @return string[]
   *   Returns an associative array with separator values as keys and
   *   human-readable translated names as values.
   */
  protected static function getDecimalSeparators() {
    return [
      '.' => t('Decimal point'),
      ',' => t('Comma'),
    ];
  }

  /**
   * Returns an array of list styles.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getListStyles() {
    return [
      'span' => t('Single line list'),
      'ol'   => t('Numbered list'),
      'ul'   => t('Bulleted list'),
      'div'  => t('Non-bulleted block list'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        // Notation style.
        'notationStyle'      => 'basicnumber',
        'exponentStyle'      => 'enotation',

        // Precision.
        'decimalDigits'      => '',
        'decimalSeparator'   => '.',

        // Thousands.
        'useThousands'       => FALSE,
        'thousandsSeparator' => ',',

        // Signs.
        'negativeStyle'      => 'minus',
        'positiveStyle'      => 'omit',

        // Padding.
        'useZeroPadding'     => FALSE,
        'paddingWidth'       => '',

        // Number base.
        'numberBase'         => 10,

        // Prefix & suffix.
        'usePrefixAndSuffix' => TRUE,

        // List style.
        'listStyle'          => 'span',
        'listSeparator'      => ', ',
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Sanitize and get current settings.
    $this->sanitizeSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    // Summarize.
    $summary = [];

    $fieldType = $this->fieldDefinition->getType();

    if ($fieldType === 'integer') {
      $sample = -1234;
    }
    else {
      $sample = -1234.567890;
    }

    // Formatting a number can introduced HTML. To preserve it during
    // presentation, call it formatted markup.
    $value = $this->numberFormat($sample);
    $value = new FormattableMarkup($value, []);
    $summary[] = $this->t(
      'Sample: @value',
      [
        '@value' => $value,
      ]);

    // If the field can store multiple values, then summarize list style.
    if ($isMultiple === TRUE) {
      $listStyles    = $this->getListStyles();
      $listStyle     = $this->getSetting('listStyle');
      $listSeparator = $this->getSetting('listSeparator');

      $text = $listStyles[$listStyle];
      if ($listStyle === 'span' && empty($listSeparator) === FALSE) {
        $text .= $this->t(', with separator');
      }
      $summary[] = $text;
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
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    if ($isMultiple === TRUE) {
      return $this->t('Format numbers in a variety of notation styles. Multiple field values are shown as a list on one line, bulleted, numbered, or in blocks.');
    }

    return $this->t('Format a number in a variety of notation styles.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $this->sanitizeSettings();
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    // Below, some checkboxes and select choices show/hide other form
    // elements. We use Drupal's obscure 'states' feature, which adds
    // Javascript to elements to auto show/hide based upon a set of
    // simple conditions.
    //
    // Those conditions need to reference the form elements to check
    // (e.g. a checkbox), but the element ID and name are automatically
    // generated by the parent form. We cannot set them, or predict them,
    // so we cannot use them. We could use a class, but this form may be
    // shown multiple times on the same page, so a simple class would not be
    // unique. Instead, we create classes for this form only by adding a
    // random number marker to the end of the class name.
    $marker = rand();

    // Add branding.
    $elements = [];
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

    // Prompt for notation and exponent styles.
    $elements['notationStyle'] = [
      '#title'         => $this->t('Notation style'),
      '#type'          => 'select',
      '#options'       => $this->getNotationStyles(),
      '#default_value' => $this->getSetting('notationStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-notation-style',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'notationStyle-' . $marker,
        ],
      ],
    ];

    $elements['exponentStyle'] = [
      '#title'         => $this->t('Exponent style'),
      '#type'          => 'select',
      '#options'       => $this->getExponentStyles(),
      '#default_value' => $this->getSetting('exponentStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-exponent-style',
        ],
      ],
      '#states'        => [
        // Visible only if using "scientific" notation.
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'scientific',
          ],
        ],
      ],
    ];

    $elements['numberBase'] = [
      '#title'         => $this->t('Number base'),
      '#type'          => 'number',
      '#min'           => 2,
      '#max'           => 36,
      '#default_value' => $this->getSetting('numberBase'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-number-base',
        ],
      ],
      '#states'        => [
        // Visible only if using "numeralsystem" notation.
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'numeralsystem',
          ],
        ],
      ],
    ];

    // Basics.
    $elements['decimalDigits'] = [
      '#title'         => $this->t('Decimal digits'),
      '#type'          => 'number',
      '#min'           => 0,
      '#max'           => 10,
      '#default_value' => $this->getSetting('decimalDigits'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-decimal-digits',
        ],
      ],
      '#states'        => [
        // Invisible if using "numeralsystem" notation.
        'invisible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'numeralsystem',
          ],
        ],
      ],
    ];

    $elements['decimalSeparator'] = [
      '#title'         => $this->t('Separator'),
      '#type'          => 'select',
      '#options'       => $this->getDecimalSeparators(),
      '#default_value' => $this->getSetting('decimalSeparator'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-decimal-separator',
        ],
      ],
      '#states'        => [
        // Visible if using "generalnumber" notation.
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'generalnumber',
          ],
        ],
      ],
    ];

    $elements['useThousands'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show thousands separator'),
      '#default_value' => $this->getSetting('useThousands'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-use-thousands-separator',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'useThousands-' . $marker,
        ],
      ],
      '#states'        => [
        // Invisible if using "scientific" or "numeralsystem" notation.
        'invisible'    => [
          [
            '.notationStyle-' . $marker => [
              'value'    => 'scientific',
            ],
          ],
          [
            '.notationStyle-' . $marker => [
              'value'    => 'numeralsystem',
            ],
          ],
        ],
      ],
    ];

    $elements['thousandsSeparator'] = [
      '#title'         => $this->t('Separator'),
      '#type'          => 'select',
      '#options'       => $this->getThousandsSeparators(),
      '#default_value' => $this->getSetting('thousandsSeparator'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-thousands-separator',
        ],
      ],
      '#states'        => [
        // Visible if using "generalnumber" notation AND thousands enabled.
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'generalnumber',
          ],
          '.useThousands-' . $marker => [
            'checked'  => TRUE,
          ],
        ],
      ],
    ];

    // Positive and negative styles.
    $elements['positiveStyle'] = [
      '#title'         => $this->t('Positive style'),
      '#type'          => 'select',
      '#options'       => $this->getPositiveStyles(),
      '#default_value' => $this->getSetting('positiveStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-positive-style',
        ],
      ],
      '#states'        => [
        // Visible if using "generalnumber".
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'generalnumber',
          ],
        ],
      ],
    ];
    $elements['negativeStyle'] = [
      '#title'         => $this->t('Negative style'),
      '#type'          => 'select',
      '#options'       => $this->getNegativeStyles(),
      '#default_value' => $this->getSetting('negativeStyle'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-negative-style',
        ],
      ],
      '#states'        => [
        // Visible if using "generalnumber".
        'visible'      => [
          '.notationStyle-' . $marker => [
            'value'    => 'generalnumber',
          ],
        ],
      ],
    ];

    // Padding.
    $elements['useZeroPadding'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Pad with zeroes'),
      '#default_value' => $this->getSetting('useZeroPadding'),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-use-zero-padding',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'useZeroPadding-' . $marker,
        ],
      ],
      '#states'        => [
        // Visible if using 'generalnumber' notation style and thousands
        // enabled, OR if using 'numeralsystem' notation style.
        'visible'      => [
          [
            '.notationStyle-' . $marker => [
              'value'    => 'generalnumber',
            ],
            '.useThousands-' . $marker => [
              'unchecked'  => TRUE,
            ],
          ],
          [
            '.notationStyle-' . $marker => [
              'value'    => 'numeralsystem',
            ],
          ],
        ],
      ],
    ];
    $elements['paddingWidth'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Padding width'),
      '#default_value' => $this->getSetting('paddingWidth'),
      '#min'           => 0,
      '#max'           => 40,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-padding-width',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'formatter_suite-settings-indent',
        ],
      ],
      '#states'        => [
        // Visible if using 'generalnumber' notation style and thousands
        // enabled and zero padding enabled, OR if using 'numeralsystem'
        // notation style and zero padding enabled.
        'visible'      => [
          [
            '.notationStyle-' . $marker => [
              'value'    => 'generalnumber',
            ],
            '.useThousands-' . $marker => [
              'unchecked'  => TRUE,
            ],
            '.useZeroPadding-' . $marker => [
              'checked'  => TRUE,
            ],
          ],
          [
            '.notationStyle-' . $marker => [
              'value'    => 'numeralsystem',
            ],
            '.useZeroPadding-' . $marker => [
              'checked'  => TRUE,
            ],
          ],
        ],
      ],
    ];

    // Prefix & suffix.
    $elements['usePrefixAndSuffix'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Show field definition's prefix &amp; suffix"),
      '#default_value' => $this->getSetting('usePrefixAndSuffix'),
      '#description'   => $this->t("These may be currency symbols or units of measure. Set these on the field's definition."),
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-use-prefix-and-suffix',
        ],
      ],
    ];

    if ($isMultiple === TRUE) {
      $weight = 1000;
      $elements['sectionBreak3'] = [
        '#markup' => '<div class="formatter_suite-section-break"></div>',
        '#weight' => $weight++,
      ];

      $elements['listStyle'] = [
        '#title'         => $this->t('List style'),
        '#type'          => 'select',
        '#options'       => $this->getListStyles(),
        '#default_value' => $this->getSetting('listStyle'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-number-list-style',
          ],
        ],
        '#attributes'    => [
          'class'        => [
            'listStyle-' . $marker,
          ],
        ],
      ];

      $elements['listSeparator'] = [
        '#title'         => $this->t('Separator'),
        '#type'          => 'textfield',
        '#size'          => 10,
        '#default_value' => $this->getSetting('listSeparator'),
        '#weight'        => $weight++,
        '#wrapper_attributes' => [
          'class'        => [
            'formatter_suite-general-number-list-separator',
          ],
        ],
        '#states'        => [
          'visible'      => [
            '.listStyle-' . $marker => [
              'value'    => 'span',
            ],
          ],
        ],
      ];
    }

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
    // Get current settings.
    $notationStyle      = $this->getSetting('notationStyle');
    $exponentStyle      = $this->getSetting('exponentStyle');
    $numberBase         = $this->getSetting('numberBase');
    $decimalDigits      = $this->getSetting('decimalDigits');
    $decimalSeparator   = $this->getSetting('decimalSeparator');
    $useThousands       = $this->getSetting('useThousands');
    $thousandsSeparator = $this->getSetting('thousandsSeparator');
    $positiveStyle      = $this->getSetting('positiveStyle');
    $negativeStyle      = $this->getSetting('negativeStyle');
    $useZeroPadding     = $this->getSetting('useZeroPadding');
    $paddingWidth       = $this->getSetting('paddingWidth');
    $usePrefixAndSuffix = $this->getSetting('usePrefixAndSuffix');

    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();

    // Get the field type.
    $fieldType = $this->fieldDefinition->getType();
    if ($fieldType === 'decimal') {
      $fieldSettings  = $this->getFieldSettings();
      $fieldPrecision = $fieldSettings['precision'];
      $fieldScale     = $fieldSettings['scale'];
    }
    else {
      $fieldPrecision = 0;
      $fieldScale     = 0;
    }

    // Get setting defaults.
    $defaults = $this->defaultSettings();

    // Sanitize & validate.
    //
    // While <select> inputs constrain choices to those we define in the
    // form, it is possible to hack a form response and send other values
    // back. So check all <select> choices and use the default when a
    // value is empty or unknown.
    $notationStyles = $this->getNotationStyles();
    if (empty($notationStyle) === TRUE ||
        isset($notationStyles[$notationStyle]) === FALSE) {
      $notationStyle = $defaults['notationStyle'];
    }

    $exponentStyles = $this->getExponentStyles();
    if (empty($exponentStyle) === TRUE ||
        isset($exponentStyles[$exponentStyle]) === FALSE) {
      $exponentStyle = $defaults['exponentStyle'];
    }

    $negativeStyles = $this->getNegativeStyles();
    if (empty($negativeStyle) === TRUE ||
        isset($negativeStyles[$negativeStyle]) === FALSE) {
      $negativeStyle = $defaults['negativeStyle'];
    }

    $positiveStyles = $this->getPositiveStyles();
    if (empty($positiveStyle) === TRUE ||
        isset($positiveStyles[$positiveStyle]) === FALSE) {
      $positiveStyle = $defaults['positiveStyle'];
    }

    $thousandsSeparators = $this->getThousandsSeparators();
    if (empty($thousandsSeparator) === TRUE ||
        isset($thousandsSeparators[$thousandsSeparator]) === FALSE) {
      $thousandsSeparator = $defaults['thousandsSeparator'];
    }

    $decimalSeparators = $this->getDecimalSeparators();
    if (empty($decimalSeparator) === TRUE ||
        isset($decimalSeparators[$decimalSeparator]) === FALSE) {
      $decimalSeparator = $defaults['decimalSeparator'];
    }

    // Insure that boolean values are boolean.
    $useZeroPadding = boolval($useZeroPadding);

    $useThousands = boolval($useThousands);

    $usePrefixAndSuffix = boolval($usePrefixAndSuffix);

    // Insure that integer values are integers.
    //
    // Security: The number of decimal digits and the padding width are
    // both entered by the administrator. Both should be simple integers
    // and should not include HTML or HTML entities.
    //
    // Parsing these as integers ignores any additional text that might
    // be present, such as HTML or HTML entities.
    if (empty($decimalDigits) === TRUE) {
      // If the field type is "decimal", default to the decimal field's scale.
      //
      // Otherwise use the setting default.
      if ($fieldType === 'decimal') {
        $decimalDigits = $fieldScale;
      }
      elseif ($fieldType === 'integer') {
        $decimalDigits = 0;
      }
      else {
        $decimalDigits = 2;
      }
    }
    else {
      $decimalDigits = intval($decimalDigits);
    }

    if (empty($numberBase) === TRUE) {
      $numberBase = $defaults['numberBase'];
    }
    else {
      $numberBase = intval($numberBase);
      if ($numberBase < 2) {
        $numberBase = 2;
      }
      elseif ($numberBase > 36) {
        $numberBase = 36;
      }
    }

    if (empty($paddingWidth) === TRUE) {
      // If the field type is "decimal", default to the decimal field's
      // precision, plus 1 for the decimal.
      //
      // Otherwise use the setting default.
      if ($fieldType === 'decimal') {
        $paddingWidth = ((int) $fieldPrecision + 1);
      }
      else {
        $paddingWidth = 0;
      }
    }
    else {
      $paddingWidth = intval($paddingWidth);
    }

    // Set settings.
    $this->setSetting('notationStyle', $notationStyle);
    $this->setSetting('exponentStyle', $exponentStyle);
    $this->setSetting('numberBase', $numberBase);
    $this->setSetting('decimalDigits', $decimalDigits);
    $this->setSetting('decimalSeparator', $decimalSeparator);
    $this->setSetting('useThousands', $useThousands);
    $this->setSetting('thousandsSeparator', $thousandsSeparator);
    $this->setSetting('positiveStyle', $positiveStyle);
    $this->setSetting('negativeStyle', $negativeStyle);
    $this->setSetting('useZeroPadding', $useZeroPadding);
    $this->setSetting('paddingWidth', $paddingWidth);
    $this->setSetting('usePrefixAndSuffix', $usePrefixAndSuffix);

    $listStyle = $this->getSetting('listStyle');
    $listStyles = $this->getListStyles();

    if ($isMultiple === TRUE) {
      if (empty($listStyle) === TRUE ||
          isset($listStyles[$listStyle]) === FALSE) {
        $listStyle = $defaults['listStyle'];
        $this->setSetting('listStyle', $listStyle);
      }
    }
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
      $output = $this->numberFormat($item->value);

      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) === TRUE && $item->value !== $output) {
        $item->_attributes += ['content' => $item->value];
      }

      // The value may include HTML markup, so mark it as safe.
      $output = new FormattableMarkup($output, []);

      $elements[$delta] = [
        '#markup' => $output,
        '#attached' => [
          'library' => [
            'formatter_suite/formatter_suite.usage',
          ],
        ],
      ];
    }

    //
    // Add multi-value field processing.
    // ---------------------------------
    // If the field has multiple values, redirect to a theme and pass
    // the list style and separator.
    $isMultiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    if ($isMultiple === TRUE) {
      // Replace the 'field' theme with one that supports lists.
      $elements['#theme'] = 'formatter_suite_field_list';

      // Set the list style.
      $elements['#list_style'] = $this->getSetting('listStyle');

      // Set the list separator.
      //
      // Security: The list separator is entered by an administrator.
      // It may legitimately include HTML entities and minor HTML, but
      // it should not include dangerous HTML.
      //
      // Because it may include HTML, we cannot pass it as-is and let a TWIG
      // template use {{ }}, which will process the text and corrupt any
      // entered HTML or HTML entities.
      //
      // We therefore use an Xss admin filter to remove any egreggious HTML
      // (such as scripts and styles), and then FormattableMarkup() to mark the
      // resulting text as safe.
      $listSeparator = Xss::filterAdmin($this->getSetting('listSeparator'));
      $elements['#list_separator'] = new FormattableMarkup($listSeparator, []);
    }

    return $elements;
  }

  /**
   * Returns the array of field prefixes, if any.
   *
   * @return array
   *   Returns an array of 1 or 2 strings for singular and plural prefixes.
   *   If there are no prefixes, an array with '' is returned.
   */
  protected function getFieldPrefixes() {
    $fieldSettings = $this->getFieldSettings();

    // Get the prefixes and suffixes. For each one, the value is an array
    // of 1 or 2 entries for singular and optional plural values.
    $prefixes = [''];

    if (isset($fieldSettings['prefix']) === TRUE) {
      $prefixes = array_map(
        [
          'Drupal\Core\Field\FieldFilteredMarkup',
          'create',
        ],
        explode('|', $fieldSettings['prefix']));
    }

    return $prefixes;
  }

  /**
   * Returns the array of field suffixes, if any.
   *
   * @return array
   *   Returns an array of 1 or 2 strings for singular and plural suffixes.
   *   If there are no suffixes, an array with '' is returned.
   */
  protected function getFieldSuffixes() {
    $fieldSettings = $this->getFieldSettings();

    // Get the prefixes and suffixes. For each one, the value is an array
    // of 1 or 2 entries for singular and optional plural values.
    $suffixes = [''];

    if (isset($fieldSettings['suffix']) === TRUE) {
      $suffixes = array_map(
        [
          'Drupal\Core\Field\FieldFilteredMarkup',
          'create',
        ],
        explode('|', $fieldSettings['suffix']));
    }

    return $suffixes;
  }

  /**
   * Returns a prefix selected based on the value.
   *
   * If there are less than 2 prefixes, the first one is always returned.
   * Otherwise the singular or plural prefix is returned based upon the
   * given value.
   *
   * @param mixed $number
   *   The number to check for singular vs. plural.
   * @param array $prefixes
   *   An array of 1 or 2 strings for singular and plural prefixes.
   *
   * @return string
   *   Returns the chosen singular or plural prefix.
   */
  protected function selectPrefix($number, array $prefixes) {
    if (count($prefixes) === 1) {
      return $prefixes[0];
    }

    return $this->formatPlural($number, $prefixes[0], $prefixes[1]);
  }

  /**
   * Returns a suffix selected based on the value.
   *
   * If there are less than 2 suffixes, the first one is always returned.
   * Otherwise the singular or plural suffix is returned based upon the
   * given value.
   *
   * @param mixed $number
   *   The number to check for singular vs. plural.
   * @param array $suffixes
   *   An array of 1 or 2 strings for singular and plural prefixes.
   *
   * @return string
   *   Returns the chosen singular or plural suffix.
   */
  protected function selectSuffix($number, array $suffixes) {
    if (count($suffixes) === 1) {
      return $suffixes[0];
    }

    return $this->formatPlural($number, $suffixes[0], $suffixes[1]);
  }

  /**
   * Format a number using the current settings.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   The formatted number, not including the prefix or suffix.
   */
  protected function numberFormat($number) {
    // Sanitize and get current settings.
    $this->sanitizeSettings();

    // Format scientific notation separately.
    switch ($this->getSetting('notationStyle')) {
      default:
      case 'basicnumber':
        return $this->numberFormatBasic($number, '');

      case 'generalnumber':
        return $this->numberFormatGeneral($number);

      case 'numeralsystem':
        return $this->numberFormatNumeral($number);

      case 'percentage':
        return $this->numberFormatPercentage($number);

      case 'scientific':
        return $this->numberFormatScientific($number);
    }
  }

  /**
   * Formats a number using the basic set of formatting features.
   *
   * Basic number notation formats a number with two parts:
   *   - Whole part before the decimal.
   *   - Fractional part after the decimal.
   *
   * The following formatting attributes are used:
   *   - Use thousands.
   *   - Decimal digits (decimal & float types only).
   *
   * @param mixed $number
   *   The number to format.
   * @param string $preSuffix
   *   (optional, default = '') The string to add before the suffix.
   *
   * @return string
   *   The formatted number, including the prefix or suffix.
   */
  protected function numberFormatBasic($number, string $preSuffix = '') {
    // Get settings.
    $usePrefixAndSuffix = $this->getSetting('usePrefixAndSuffix');
    $useThousands = $this->getSetting('useThousands');
    $thousandsSeparator = ($useThousands === TRUE) ? ',' : '';

    if (is_int($number) === TRUE) {
      $decimalDigits = 0;
    }
    else {
      $decimalDigits = $this->getSetting('decimalDigits');
    }

    // Format the number.
    $formatted = number_format(
      $number,
      $decimalDigits,
      '.',
      $thousandsSeparator);

    // If needed, add the prefix and suffix.
    if ($usePrefixAndSuffix === FALSE) {
      return $formatted . $preSuffix;
    }

    $prefixes = $this->getFieldPrefixes();
    $suffixes = $this->getFieldSuffixes();
    $prefix = $this->selectPrefix($number, $prefixes);
    $suffix = $this->selectSuffix($number, $suffixes);

    return $prefix . $formatted . $preSuffix . $suffix;
  }

  /**
   * Formats a number using the general set of formatting features.
   *
   * General number notation formats a number with two parts:
   *   - Whole part before the decimal.
   *   - Fractional part after the decimal.
   *
   * The following formatting attributes are used:
   *   - Decimal digits (decimal & float types only).
   *   - Decimal separator.
   *   - Use thousands.
   *   - Thousand separator.
   *   - Positive style.
   *   - Negative style.
   *   - Use zero padding.
   *   - Padding width.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   The formatted number, including the prefix or suffix.
   */
  protected function numberFormatGeneral($number) {
    // Get settings.
    $usePrefixAndSuffix = $this->getSetting('usePrefixAndSuffix');
    $decimalDigits      = $this->getSetting('decimalDigits');
    $decimalSeparator   = $this->getSetting('decimalSeparator');
    $useThousands       = $this->getSetting('useThousands');
    $thousandsSeparator = $this->getSetting('thousandsSeparator');
    $positiveStyle      = $this->getSetting('positiveStyle');
    $negativeStyle      = $this->getSetting('negativeStyle');
    $useZeroPadding     = $this->getSetting('useZeroPadding');
    $paddingWidth       = $this->getSetting('paddingWidth');

    if ($useThousands === TRUE) {
      // When a thousands separator is in use, zero padding is disabled.
      $useZeroPadding = FALSE;
    }

    // If negative, set a flag and make positive. Handle the negative later
    // based upon the chosen negative style.
    $isNegative = FALSE;
    if ($number < 0 && $negativeStyle !== 'minus') {
      $isNegative = TRUE;
      $number *= -1.0;
    }

    // Split the number into whole and fraction parts.
    $whole = (int) $number;
    $fraction = ((float) $number - (float) $whole);

    // Format the whole part using the thousands separator, if any.
    if ($useThousands === FALSE) {
      $thousandsSeparator = '';
    }

    $formattedWhole = number_format($whole, 0, '', $thousandsSeparator);

    if (is_int($number) === TRUE || $decimalDigits === 0) {
      // With a zero decimalDigits, there are zero digits after the decimal.
      // So, there is no formatting of the fractional part and no use
      // of the decimal separator. The formatted whole part is the
      // formatted number.
      $formatted = $formattedWhole;
    }
    else {
      // With a positive decimalDigits, there are digits after the decimal.
      // Scale up the fraction so that the requested number of digits
      // after the decimal are now before the decimal. Then round so
      // we only have those digits as an integer.
      $fraction = (int) round(($fraction * pow(10, $decimalDigits)), 0);

      // Format the decimal part.
      $fmt = '%0' . $decimalDigits . 'd';
      $formattedFraction = sprintf($fmt, $fraction);

      // Concatenate, inserting the decimal point.
      $formatted = $formattedWhole . $decimalSeparator . $formattedFraction;
    }

    // Handle padding.
    if ($useZeroPadding === TRUE) {
      $len = mb_strlen($formatted);
      if ($len < $paddingWidth) {
        $fmt = '%0' . $paddingWidth . 's';
        $formatted = sprintf($fmt, $formatted);
      }
    }

    // Get prefix and suffix, if any. We need the prefix because it may go
    // before a '(' when formatting negatives below.
    if ($usePrefixAndSuffix === TRUE) {
      $prefixes = $this->getFieldPrefixes();
      $suffixes = $this->getFieldSuffixes();
      $prefix = $this->selectPrefix($number, $prefixes);
      $suffix = $this->selectSuffix($number, $suffixes);
    }
    else {
      $prefix = '';
      $suffix = '';
    }

    // Handle the sign, if needed.
    $addRed = FALSE;
    if ($isNegative === TRUE) {
      // Number is negative. Either add a '-' or surround with '(' and ')'.
      switch ($negativeStyle) {
        default:
        case 'minus':
          $formatted = $prefix . '-' . $formatted . $suffix;
          break;

        case 'red':
          $formatted = $prefix . $formatted . $suffix;
          $addRed = TRUE;
          break;

        case 'parenthesis':
          $formatted = '(' . $prefix . $formatted . ')' . $suffix;
          break;

        case 'redparenthesis':
          $formatted = '(' . $prefix . $formatted . ')' . $suffix;
          $addRed = TRUE;
          break;
      }

      if ($addRed === TRUE) {
        // Making the text red could be done in two ways:
        // - Add a class and then use CSS to give the text a red color.
        // - Include an inline style that makes the text red.
        //
        // The class approach works on pages that use this formatter
        // because we include the module's CSS. It also works while
        // the formatter's settings page is being show, because again
        // we include the module's CSS. But the class approach *does not*
        // work on a formatter setting page once the formatter's own
        // settings have been accepted because the module's CSS is then
        // omitted. This gives an inconsistent presentation to the
        // administrator using the settings page.
        //
        // The inline style approach works in all cases. The downside is
        // that it means a site theme cannot override the color. On the
        // other hand, *should they*?  Using red for negative values is
        // an accounting convention, so overriding it to be blue, for
        // instance, would be invalid.
        //
        // So, we intentionally use an inline style to insure the value
        // is always red in all contexts, and because themes really should
        // NOT override an accounting convention.
        $formatted = '<span style="color: red">' .
          $formatted . '</span>';
      }
    }
    elseif ($positiveStyle === 'plus') {
      // Number is positive. Add a '+'.
      $formatted = '+' . $formatted;
    }

    return $formatted;
  }

  /**
   * Formats a number using a number system base.
   *
   * Numeral system formating always rounds floating-point values to
   * an integer.
   *
   * The following formatting attributes are used:
   *   - Number base.
   *   - Use zero padding.
   *   - Padding width.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   The formatted number, including the prefix or suffix.
   */
  protected function numberFormatNumeral($number) {
    // Get settings.
    $usePrefixAndSuffix = $this->getSetting('usePrefixAndSuffix');
    $numberBase         = $this->getSetting('numberBase');
    $useZeroPadding     = $this->getSetting('useZeroPadding');
    $paddingWidth       = $this->getSetting('paddingWidth');

    // If negative, set a flag and make positive. Handle the negative later.
    $isNegative = FALSE;
    if ($number < 0) {
      $isNegative = TRUE;
      $number *= -1.0;
    }

    // Convert to integer.
    $number = round($number, 0);

    // Convert to the requested base. The result is a string.
    $formatted = base_convert($number, 10, $numberBase);

    // Handle padding.
    if ($useZeroPadding === TRUE) {
      $len = mb_strlen($formatted);
      if ($len < $paddingWidth) {
        $fmt = '%0' . $paddingWidth . 's';
        $formatted = sprintf($fmt, $formatted);
      }
    }

    // Add minus sign back in, if needed.
    if ($isNegative === TRUE) {
      $formatted = '-' . $formatted;
    }

    // If needed, add the prefix and suffix.
    if ($usePrefixAndSuffix === FALSE) {
      return $formatted;
    }

    $prefixes = $this->getFieldPrefixes();
    $suffixes = $this->getFieldSuffixes();
    $prefix = $this->selectPrefix($number, $prefixes);
    $suffix = $this->selectSuffix($number, $suffixes);

    return $prefix . $formatted . $suffix;
  }

  /**
   * Formats a number using a basic set of formatting features for percentages.
   *
   * This is the same as basic notation except:
   *   - The value is multiplied by 100 before formatting.
   *   - A '%' is always added as a suffix.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   The formatted number, including the prefix or suffix.
   */
  protected function numberFormatPercentage($number) {
    if (is_int($number) === TRUE) {
      return $this->numberFormatBasic(($number * 100), '%');
    }

    return $this->numberFormatBasic(($number * 100.0), '%');
  }

  /**
   * Formats a number using scientific notation.
   *
   * Scientific notation formats a number with three parts:
   *   - Whole part before the decimal.
   *   - Fractional part after the decimal.
   *   - Exponent for a power of 10.
   *
   * Scientific notation follows international standards, so it is not
   * subject to regional choices for a decimal separator and it does not
   * support different positive and negative presentation styles.
   *
   * The following formatting attributes are used:
   *   - Exponent style.
   *   - Decimal digits.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   The formatted number, including the prefix or suffix.
   */
  protected function numberFormatScientific($number) {
    // Get settings.
    $usePrefixAndSuffix = $this->getSetting('usePrefixAndSuffix');
    $exponentStyle = $this->getSetting('exponentStyle');
    $decimalDigits = $this->getSetting('decimalDigits');

    // Use sprintf() to do an initial format into E-notation (the only
    // one sprintf() supports). sprintf() will handle scaling up/down the
    // number to a whole part with one digit, then calculating the
    // exponent.
    $fmt = '%' . ($decimalDigits + 2) . '.' . $decimalDigits . 'E';
    $formatted = sprintf($fmt, (float) $number);

    // Format the exponent.
    switch ($exponentStyle) {
      default:
      case 'enotation':
        // Use sprintf() output as-is since it produces E-notation.
        break;

      case 'superscript':
        // Split the formatted output at the 'E' into mantissa and exponent.
        // If this doesn't yield two parts, then the number was too small
        // and simple to have an exponent (e.g. "1.0"). Just use the
        // number as-is.
        $parts = explode('E', $formatted);
        if (count($parts) === 2) {
          $formattedMantissa = $parts[0];
          $formattedExponent = $parts[1];

          // Format the exponent using an HTML <sup> tag.
          $formatted = $formattedMantissa . 'x10<sup>' .
            $formattedExponent . '</sup>';
        }
        break;
    }

    // If needed, add the prefix and suffix.
    if ($usePrefixAndSuffix === FALSE) {
      return $formatted;
    }

    $prefixes = $this->getFieldPrefixes();
    $suffixes = $this->getFieldSuffixes();
    $prefix = $this->selectPrefix($number, $prefixes);
    $suffix = $this->selectSuffix($number, $suffixes);

    return $prefix . $formatted . $suffix;
  }

}
