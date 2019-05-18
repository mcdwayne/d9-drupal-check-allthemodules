<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

use Drupal\formatter_suite\Branding;

/**
 * Formats text with expand/collapse buttons to show more/less.
 *
 * Long text fields may have long text. When presented along with other
 * content on the same page, the text can be overwhelming and make it hard
 * to find the other content. This formatter temporarily shortens the long
 * text to a specified height and adds an "Expand" button. Clicking the
 * button expands the text display to full size, and adds a "Collapse"
 * button. Clicking that button shortes the text again.
 *
 * @FieldFormatter(
 *   id = "formatter_suite_text_with_expand_collapse_buttons",
 *   label = @Translation("Formatter Suite - Text with expand/collapse buttons"),
 *   weight = 1000,
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string_long"
 *   }
 * )
 */
class TextWithExpandCollapseButtonsFormatter extends FormatterBase {

  /*---------------------------------------------------------------------
   *
   * Configuration.
   *
   *---------------------------------------------------------------------*/

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'collapsedHeight'     => '8em',
      'expandButtonLabel'   => t('Expand...'),
      'collapseButtonLabel' => t('Collapse...'),
      'animationDuration'   => 500,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Get current settings.
    $collapsedHeight   = $this->getSetting('collapsedHeight');
    $animationDuration = $this->getSetting('animationDuration');

    // Security: The animation duration is entered by an administrator.
    // It should be a simple integer, with no other characters, HTML, or
    // HTML entities.
    //
    // By parsing it as an integer, we ignore anything else and remove
    // any security issues.
    $animationDuration = intval($animationDuration);

    // Security: The collapse height is entered by an administrator.
    // It should be a number followed by CSS units, such as "px", "pt",
    // or "em". It should not contain HTML or HTML entities.
    //
    // If integer parsing of the string yields a zero, then the string
    // is assumed to be empty or invalid and collapsing is disabled.
    // Otherwise the string is santized using an Html escape filter
    // that escapes all HTML and HTML entities. If the admin enters these,
    // the resulting string is not likely to work as a collapse height
    // and the Javascript will not get a meaningful result, but it will
    // still be safe.
    $collapsedHeight = Html::escape($collapsedHeight);
    $hasCollapseHeight = TRUE;
    if (empty($collapsedHeight) === TRUE ||
        $collapsedHeight === "0" ||
        (int) $collapsedHeight === 0) {
      $hasCollapseHeight = FALSE;
    }

    // Present.
    $summary = parent::settingsSummary();

    if ($hasCollapseHeight === FALSE) {
      $summary[] = $this->t('Disabled because no collapsed height set.');
    }
    else {
      $summary[] = $this->t(
        'Shorten long text areas to @collapsedHeight.',
        [
          '@collapsedHeight' => $collapsedHeight,
        ]);

      if ($animationDuration > 0) {
        $summary[] = $this->t(
          'Animate over @animationDuration milliseconds.',
          [
            '@animationDuration' => $animationDuration,
          ]);
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
    return $this->t('Present long text in a shortened area and include links to expand the text to full height, and collapse it back again.');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    //
    // Start with the parent form.
    $elements = parent::settingsForm($form, $formState);

    $elements['#attached'] = [
      'library' => [
        'formatter_suite/formatter_suite.settings',
      ],
    ];

    // Add branding.
    $elements = [];
    $elements = Branding::addFieldFormatterBranding($elements);
    $elements['#attached']['library'][] =
      'formatter_suite/formatter_suite.fieldformatter';

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

    // Add each of the values.
    $elements['collapsedHeight'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Collapsed height'),
      '#size'          => 10,
      '#default_value' => $this->getSetting('collapsedHeight'),
      '#description'   => $this->t("Text height when collapsed. Use CSS units (e.g. '200px', '40pt', '8em'). Empty or zero value disables."),
    ];
    $elements['collapseButtonLabel'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Collapse link label'),
      '#size'          => 10,
      '#maxlength'     => 128,
      '#default_value' => $this->getSetting('collapseButtonLabel'),
    ];
    $elements['expandButtonLabel'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Expand link label'),
      '#size'          => 10,
      '#maxlength'     => 128,
      '#default_value' => $this->getSetting('expandButtonLabel'),
    ];
    $elements['animationDuration'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Animation duration'),
      '#size'          => 10,
      '#default_value' => $this->getSetting('animationDuration'),
      '#description'   => $this->t('Animation time in milliseconds (e.g. 500 = 1/2 second). Empty or zero value disables animation.'),
    ];

    return $elements;
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
    //
    // The $items array has a list of items to format. We need to return
    // an array with identical indexing and corresponding render elements
    // for those items.
    if (empty($items) === TRUE) {
      return [];
    }

    // Get current settings.
    $collapsedHeight     = $this->getSetting('collapsedHeight');
    $animationDuration   = $this->getSetting('animationDuration');
    $collapseButtonLabel = $this->getSetting('collapseButtonLabel');
    $expandButtonLabel   = $this->getSetting('expandButtonLabel');

    // Security: The button labels are entered by an administrator.
    // They may legitimately include HTML entities and minor HTML, but
    // they should not include dangerous HTML.
    //
    // Because they may include HTML, we cannot pass them directly to t()
    // or let a TWIG template use {{ }}, both of which will process
    // the text and corrupt any entered HTML or HTML entities. We also
    // do not want the administrator's labels translated.
    //
    // We therefore use an Xss filter to remove any egreggious HTML
    // (such as scripts). Below we'll include the label text within
    // markup.
    $collapseButtonLabel = Xss::filterAdmin($collapseButtonLabel);
    $expandButtonLabel = Xss::filterAdmin($expandButtonLabel);

    // Security: The animation duration is entered by an administrator.
    // It should be a simple integer, with no other characters, HTML, or
    // HTML entities.
    //
    // By parsing it as an integer, we ignore anything else and remove
    // any security issues.
    $animationDuration = intval($animationDuration);

    // Security: The collapse height is entered by an administrator.
    // It should be a number followed by CSS units, such as "px", "pt",
    // or "em". It should not contain HTML or HTML entities.
    //
    // If integer parsing of the string yields a zero, then the string
    // is assumed to be empty or invalid and collapsing is disabled.
    // Otherwise the string is santized using an Html escape filter
    // that escapes all HTML and HTML entities. If the admin enters these,
    // the resulting string is not likely to work as a collapse height
    // and the Javascript will not get a meaningful result, but it will
    // still be safe.
    $collapsedHeight = Html::escape($collapsedHeight);
    $hasCollapsedHeight = TRUE;
    if (empty($collapsedHeight) === TRUE ||
        $collapsedHeight === "0" ||
        (int) $collapsedHeight === 0) {
      $hasCollapsedHeight = FALSE;
    }

    // If there is no collapsed height, show text full height.
    $build = [];
    if ($hasCollapsedHeight === FALSE) {
      foreach ($items as $delta => $item) {
        $build[$delta] = [
          '#type'     => 'processed_text',
          '#text'     => $item->value,
          '#format'   => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }

      return $build;
    }

    // Nest the text, add buttons, and add a behavior script.
    foreach ($items as $delta => $item) {
      $build[$delta] = [
        '#type'     => 'container',
        '#attributes' => [
          'class'     => ['formatter_suite-text-with-expand-collapse-buttons'],
        ],
        '#attached'   => [
          'library'   => [
            'formatter_suite/formatter_suite.usage',
            'formatter_suite/formatter_suite.text_with_expand_collapse_buttons',
          ],
        ],
        'text'          => [
          '#type'       => 'container',
          '#attributes' => [
            'class'     => ['formatter_suite-text'],
            'data-formatter_suite-collapsed-height'   => $collapsedHeight,
            'data-formatter_suite-animation-duration' => $animationDuration,
          ],
          'processedtext' => [
            '#type'     => 'processed_text',
            '#text'     => $item->value,
            '#format'   => $item->format,
            '#langcode' => $item->getLangcode(),
          ],
        ],
        'collapse'      => [
          '#type'       => 'html_tag',
          '#tag'        => 'div',
          '#value'      => '<a href="#">' . $collapseButtonLabel . '</a>',
          '#attributes' => [
            'class'     => ['formatter_suite-text-collapse-button'],
            'style'     => 'display: none',
          ],
        ],
        'expand'        => [
          '#type'       => 'html_tag',
          '#tag'        => 'div',
          '#value'      => '<a href="#">' . $expandButtonLabel . '</a>',
          '#attributes' => [
            'class'     => ['formatter_suite-text-expand-button'],
            'style'     => 'display: none',
          ],
        ],
      ];
    }

    return $build;
  }

}
