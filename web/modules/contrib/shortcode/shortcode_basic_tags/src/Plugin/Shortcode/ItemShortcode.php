<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "item",
 *   title = @Translation("Item"),
 *   description = @Translation("Insert div or span around the text with some css classes.")
 * )
 */
class ItemShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'class' => '',
      'id' => '',
      'style' => '',
    // Default element to div.
      'type' => 'div',
    ],
      $attributes
    );

    // Only allow allowed types, and replace common shorthands.
    // TODO: Use map.
    // TODO: Allow any type the user enters?
    switch ($attributes['type']) {
      case 's':
      case 'span':
        $attributes['type'] = 'span';
        break;

      case 'd':
      default:
        $attributes['type'] = 'div';
        break;
    }

    // Build element attributes to be used in twig.
    $element_attributes = [
      'class' => $attributes['class'],
      'id' => $attributes['id'],
      'style' => $attributes['style'],
    ];

    // Filter away empty attributes.
    $element_attributes = array_filter($element_attributes);

    $output = [
      '#theme' => 'shortcode_item',
      '#type' => $attributes['type'],
      '#attributes' => $element_attributes,
      '#text' => $text,
    ];
    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[item (class="additional class"|id=item id|type=div,d,span,s)]text[/item]') . '</strong> ';
    if ($long) {
      $output[] = $this->t('Inserts an html item (type parameter = div or span) around the given text.') . '</p>';
      $output[] = '<p>' . $this->t('Additional class names can be added by the <em>class</em> parameter. The id parameter gives the html an unique css id.') . '</p>';
    }
    else {
      $output[] = $this->t('Inserts an html item (div or span) around the given text.') . '</p>';
    }

    return implode(' ', $output);
  }

}
