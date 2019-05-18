<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Formats with a variety of notation styles and includes field min/max.
 *
 * Drupal's numeric field types may include optional min and max values
 * used to constrain input. This formatter presents min/max along with
 * the field's value using a variety of common formats, or a custom
 * format. Common formats include:
 *   - "N out of MAX".
 *   - "N/MAX".
 *   - "N in [MIN,MAX]".
 *   - "N E {MIN...MAX}".
 *   - "MIN <= N <= MAX".
 *
 * The field's prefix and suffix are supported, if provided.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id         = "formatter_suite_general_number_with_min_max",
 *   label      = @Translation("Formatter Suite - General number with min/max"),
 *   weight      = 1004,
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *   }
 * )
 */
class GeneralNumberWithMinMaxFormatter extends GeneralNumberFormatter {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns an array of common formats.
   *
   * @return string[]
   *   Returns an associative array with internal names as keys and
   *   human-readable translated names as values.
   */
  protected static function getCommonFormats() {
    return [
      'N_slash_MAX'       => t('VALUE/MAX'),
      'N_out_of_MAX'      => t('VALUE out of MAX'),
      'N_in_MIN_MAX'      => t('VALUE in [MIN,MAX]'),
      'N_element_MIN_MAX' => t('VALUE &isin; {MIN...MAX}'),
      'MIN_N_MAX'         => t('MIN &le; VALUE &le; MAX'),
      'custom'            => t('Custom'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array_merge(
      [
        'commonFormat' => 'N_out_of_MAX',
        'customFormat' => '',
      ],
      parent::defaultSettings());
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $this->sanitizeSettings();

    // Get settings.
    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];

    // Sanitize & validate.
    $disabled = FALSE;
    if (isset($min) === FALSE || isset($max) === FALSE) {
      $disabled = TRUE;
    }

    // Get sample number.
    if (isset($min) === FALSE && isset($max) === FALSE) {
      // No min or max. Just use a chosen number.
      $sample = 1234.1234567890;
    }
    elseif (isset($min) === FALSE && isset($max) === TRUE) {
      // No min. Use max.
      $sample = $max;
    }
    elseif (isset($min) === TRUE && isset($max) === FALSE) {
      // No max. Use min.
      $sample = $min;
    }
    else {
      // Max and min, so use midpoint.
      $sample = ((($max - $min) / 2) + $min);
    }

    // Summarize.
    $summary = [];
    if ($disabled === TRUE) {
      $summary[] = $this->t('Disabled min/max formatting, field min/max need to be set.');
      return $summary;
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
    return $this->t('Format values along with the field\'s minimum and maximum, such as "0 &le; 5 &le; 10", "5 in [0,10]", or "5 out of 10". Use one of the available formats, or create a custom format.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    // Get the parent's form, which includes a lot of settings for
    // formatting numbers.
    $elements = parent::settingsForm($form, $formState);

    // Remove the parent's prefix/suffix support. This is handled explicitly
    // by the format here.
    unset($elements['usePrefixAndSuffix']);

    // Add warning if min/max are not set.
    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];

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

    $disabled = FALSE;
    if (isset($min) === FALSE && isset($max) === FALSE) {
      $disabled = TRUE;
      $elements['warning'] = [
        '#type'          => 'html_tag',
        '#tag'           => 'div',
        '#value'         => $this->t("To enable the display of a field's minimum and maximum, first set these in the field's definition."),
        '#weight'        => -999,
        '#attributes'    => [
          'class'        => [
            'formatter_suite-settings-warning',
          ],
        ],
      ];
    }

    $weight = -100;

    // Prompt for each setting.
    $elements['commonFormat'] = [
      '#title'         => $this->t('Min/max format:'),
      '#type'          => 'select',
      '#options'       => $this->getCommonFormats(),
      '#default_value' => $this->getSetting('commonFormat'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-minmax-common-format',
        ],
      ],
      '#attributes'    => [
        'class'        => [
          'commonFormat-' . $marker,
        ],
      ],
    ];

    $elements['customFormat'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Custom:'),
      '#size'          => 30,
      '#default_value' => $this->getSetting('customFormat'),
      '#description'   => $this->t('The symbols @value, @min, @max, @prefix, and @suffix are replaced with the field\'s value, and parameters from the field\'s definition. For example, "@value out of @max" formats as "5 out of 10".'),
      '#disabled'      => $disabled,
      '#weight'        => $weight++,
      '#wrapper_attributes' => [
        'class'        => [
          'formatter_suite-general-number-with-minmax-custom-format',
        ],
      ],
      '#states'        => [
        'visible'      => [
          '.commonFormat-' . $marker => [
            'value'    => 'custom',
          ],
        ],
      ],
    ];

    $elements['sectionBreak'] = [
      '#markup' => '<div class="formatter_suite-section-break"></div>',
      '#weight' => $weight++,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeSettings() {
    // Get settings.
    $commonFormat  = $this->getSetting('commonFormat');
    $commonFormats = $this->getCommonFormats();
    $defaults      = $this->defaultSettings();

    // Sanitize & validate.
    parent::sanitizeSettings();

    // Disable the parent's prefix/suffix support.
    $this->setSetting('usePrefixAndSuffix', FALSE);

    if (empty($commonFormat) === TRUE ||
        isset($commonFormats[$commonFormat]) === FALSE) {
      $commonFormat = $defaults['commonFormat'];
      $this->setSetting('commonFormat', $commonFormat);
    }

    // The custom format string is sanitized later during use.
  }

  /*---------------------------------------------------------------------
   *
   * View.
   *
   *---------------------------------------------------------------------*/

  /**
   * Returns a formatted number, including min, max, prefix, and suffix.
   *
   * The current settings are used to get the format and fill it in.
   *
   * The returned string is not automatically translated. Any prefix, suffix,
   * or custom format entered by the site admin is used as-is, after security
   * checks.
   *
   * @param mixed $number
   *   The number to format.
   *
   * @return string
   *   Returns the formatted number.
   */
  protected function numberFormat($number) {
    // Get settings.
    $fieldSettings = $this->getFieldSettings();
    $min           = $fieldSettings['min'];
    $max           = $fieldSettings['max'];
    $format        = $this->getSetting('customFormat');
    $commonFormat  = $this->getSetting('commonFormat');

    $prefixes = $this->getFieldPrefixes();
    $suffixes = $this->getFieldSuffixes();
    $prefix = $this->selectPrefix($number, $prefixes);
    $suffix = $this->selectSuffix($number, $suffixes);

    // Sanitize and validate.
    $hasMin = (isset($min) === TRUE);
    $hasMax = (isset($max) === TRUE);

    // Format min, max, and number.
    $formattedNumber = parent::numberFormat($number);
    $formattedMin    = ($hasMin === TRUE) ? parent::numberFormat($min) : '';
    $formattedMax    = ($hasMax === TRUE) ? parent::numberFormat($max) : '';

    // Build the argument list for use in t() below with the chosen format.
    $args = [
      '@prefix' => $prefix,
      '@suffix' => $suffix,
      '@value'  => $formattedNumber,
      '@min'    => $formattedMin,
      '@max'    => $formattedMax,
    ];

    // Format the result.
    //
    // Security: A custom format may have been entered by the administrator,
    // or we may be using a built-in common format. In either case, the
    // format may legitimately include HTML entities and minor HTML. It
    // should not include dangerous HTML.
    //
    // For the common formats, passing the format text to t() automatically
    // handles sanitizing any HTML that might be in the format. It also handles
    // possible automatic translation.
    //
    // For a custom format, we could pass it to t() as well, but Drupal's
    // style checking scripts object. The format text for t() is supposed to
    // be a literal so that Drupal's static code scanning can build up a list
    // of translatable text. In this case, however, we cannot provide literal
    // text since the text was entered by the site admin. All we can do is
    // bypass t() and the underlying TranslatableMarkup.
    switch ($commonFormat) {
      case 'N_slash_MAX':
        // Common format: N/MAX.
        if ($hasMax === FALSE) {
          // Fall thru to default.
          break;
        }
        return $this->t('@prefix@value/@prefix@max@suffix', $args);

      case 'N_out_of_MAX':
        // Common format: N out of MAX.
        if ($hasMax === FALSE) {
          // Fall thru to default.
          break;
        }
        return $this->t('@prefix@value out of @prefix@max@suffix', $args);

      case 'N_in_MIN_MAX':
        // Common format: N in [MIN:MAX].
        if ($hasMin === FALSE || $hasMax === FALSE) {
          // Fall thru to default.
          break;
        }
        return $this->t('@prefix@value in [@prefix@min,@prefix@max]@suffix', $args);

      case 'N_element_MIN_MAX':
        // Common format: N E {MIN...MAX}.
        if ($hasMin === FALSE || $hasMax === FALSE) {
          // Fall thru to default.
          break;
        }
        return $this->t('@prefix@value &isin; {@prefix@min...@prefix@max}@suffix', $args);

      case 'MIN_N_MAX':
        // Common format: MIN <= N <= MAX.
        if ($hasMin === FALSE && $hasMax === FALSE) {
          // Fall thru to default.
          break;
        }

        if ($hasMin === FALSE) {
          return $this->t('@prefix@value &le; @prefix@max@suffix', $args);
        }

        if ($hasMax === FALSE) {
          return $this->t('@prefix@min &le; @prefix@value@suffix', $args);
        }
        return $this->t('@prefix@min &le; @prefix@value &le; @prefix@max@suffix', $args);

      default:
      case 'custom':
        // Custom format.
        if (empty($format) === TRUE) {
          // Fall thru to default.
          break;
        }

        // Escape the site admin-entered format string to make it safe.
        $safeFormat = Html::escape($format);

        // Since the admin-entered text is not a literal and could not have
        // been picked up by Drupal's static code scanning to build a
        // translation, it won't be translatable. Just format it as-is.
        return new FormattableMarkup($safeFormat, $args);
    }

    // Otherwise use the default format.
    return $this->t('@prefix@value@suffix', $args);
  }

}
