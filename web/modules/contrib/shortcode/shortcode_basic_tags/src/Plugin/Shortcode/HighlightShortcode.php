<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Wraps content in a div with class highlight.
 *
 * @Shortcode(
 *   id = "highlight",
 *   title = @Translation("Highlight"),
 *   description = @Translation("Highlights text by wrapping in a span with class highlight")
 * )
 */
class HighlightShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'class' => '',
    ],
      $attributes
    );

    $class = $this->addClass($attributes['class'], 'highlight');
    return '<span class="' . $class . '">' . $text . '</span>';
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[highlight (class="additional class")]text[/highlight]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Inserts span.highlight around the text.') . '</p>';
      $output[] = '<p>' . $this->t('Sample css:') . '</p>';
      $output[] = '
      <code>
        span.highlight{
            background-color:red;
        }
        span.highlight2{
            background-color:cyan;
        }
      </code><p></p>';
    }
    else {
      $output[] = $this->t('Inserts span.highlight around the text. Additional class names can be added by the <em>class</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
