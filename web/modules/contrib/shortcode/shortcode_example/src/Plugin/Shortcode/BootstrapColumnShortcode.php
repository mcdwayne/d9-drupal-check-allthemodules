<?php

namespace Drupal\shortcode_example\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for bootstrap columns.
 *
 * @Shortcode(
 *   id = "col",
 *   title = @Translation("Bootstrap column"),
 *   description = @Translation("Builds a div with bootstrap column size classes")
 * )
 */
class BootstrapColumnShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes([
      'class' => '',
      'xs' => '',
      'sm' => '',
      'md' => '',
      'lg' => '',
    ],
      $attributes
    );

    $class = $attributes['class'];
    foreach (['xs', 'sm', 'md', 'lg'] as $size) {
      if ($attributes[$size]) {
        $class = $this->addClass($class, 'col-' . $size . '-' . $attributes[$size]);
      }
    }
    return '<div class="' . $class . '">' . $text . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[col class="custom-class" xs="12" sm="6" md="4" lg="3"]Other HTML content here [/col]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Wraps your content with a div with bootstrap column size classes. All attributes are optional but it would not be very useful unless you define at least 1 size attribute or custom all the classes yourself using class. Setting md=4 translates to the col-md-4 class, etc.') . '</p>';
    }
    else {
      $output[] = $this->t('Wraps your content with a div with bootstrap column size classes.') . '</p>';
    }

    return implode(' ', $output);
  }

}
