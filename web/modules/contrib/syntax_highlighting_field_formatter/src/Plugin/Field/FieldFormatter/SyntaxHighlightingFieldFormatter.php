<?php

/**
 * @file
 * Contains Drupal\syntax_highlighting_field_formatter\Plugin\Field\FieldFormatter\SyntaxHighlightingFieldFormatter.
 */

namespace Drupal\syntax_highlighting_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'syntax_highlighting_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "syntax_highlighting_field_formatter",
 *   label = @Translation("Syntax highlighting field formatter"),
 *   field_types = {
 *     "string_long", "text_long", "text", "text_with_summary"
 *   }
 * )
 */
class SyntaxHighlightingFieldFormatter extends FormatterBase {
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
      $elements[$delta] = ['#markup' => new FormattableMarkup($this->viewValue($item), array())];
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
    return $this->getHightlightedString($item->value);
  }

  /**
   * Gets a syntax highlighted string. Uses PHP's bulit-in function
   * highlight_string() to highlight syntax.
   *
   * @return mixed
   */
  private function getHightlightedString($value) {
    $sHighlighted = highlight_string('<?php ' .
      $value . ' ?>', TRUE);

    $sHighlighted = str_replace('<span style="color: #0000BB">&lt;?php&nbsp;</span>',
      '<span style="color: #000000">', $sHighlighted);
    $sHighlighted = str_replace('<span style="color: #0000BB">?&gt;</span>',
      '', $sHighlighted);
    $sHighlighted = str_replace('&nbsp;?&gt;',
      '', $sHighlighted);
    $sHighlighted = str_replace('&lt;?php&nbsp;',
      '', $sHighlighted);

    return '<blockquote>' . $sHighlighted . '</blockquote>';
  }

}
