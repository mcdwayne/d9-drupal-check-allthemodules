<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Replace the given text formatted like as a dropcap.
 *
 * @Shortcode(
 *   id = "dropcap",
 *   title = @Translation("Dropcap"),
 *   description = @Translation("Replace the given text formatted like as a dropcap.")
 * )
 */
class DropcapShortcode extends ShortcodeBase {

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

    $class = $this->addClass($attributes['class'], 'dropcap');

    $output = [
      '#theme' => 'shortcode_dropcap',
      '#class' => $class,
      '#text' => $text,
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[dropcap (class="additional class")]text[/dropcap]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Makes dropcap from the text.') . '</p>';
      $output[] = '<p>' . $this->t('Sample css:') . '</p>';
      $output[] = '<code>
        .dropcap {
          display:block;
          float:left;
          font-size:38px;
          line-height:38px;
          vertical-align:baseline;
          padding-right:5px;
        }
        </code><p></p>';
    }
    else {
      $output[] = $this->t('Makes dropcap from the text. Additional class names can be added by the <em>class</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
