<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Replace the given text formatted like as a quote.
 *
 * @Shortcode(
 *   id = "quote",
 *   title = @Translation("Quote"),
 *   description = @Translation("Replace the given text formatted like as a quote.")
 * )
 */
class QuoteShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'class' => '',
      'author' => '',
    ],
      $attributes
    );

    $class = $this->addClass($attributes['class'], 'quote');

    $output = [
      '#theme' => 'shortcode_quote',
      '#class' => $class,
      '#author' => $attributes['author'],
      '#text' => $text,
    ];
    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[quote (class="additional class" | author="author name")]text[/quote]') . '</strong>';
    if ($long) {
      $output[] = $this->t('Formats the text like as a quote.') . '</p>';
      $output[] = '<p>' . $this->t('Sample css:') . '</p>';
      $output[] = '
        <code>
          .quote {
             display:block;
             float:left;
             width:30%;
             margin:20px;
             margin-left:0;
             padding:5px 0 5px 20px;
             font-style:italic;
             border-left:3px solid #E8E8E8;
             line-heigh:1.5em;
             font-size:14px;
             letter-spacing: 1px;
             word-spacing: 2px;
          }

          .quote.right{
            float:right;
            margin-right:0;
            margin-left:20px;
          }
        </code><p></p>';
    }
    else {
      $output[] = $this->t('Formats the text like as a quote. Additional class names can be added by the <em>class</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
