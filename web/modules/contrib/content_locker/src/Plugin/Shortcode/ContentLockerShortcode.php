<?php

namespace Drupal\content_locker\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Core\Field\FieldFilteredMarkup;

/**
 * The content locker shortcode.
 *
 * @Shortcode(
 *   id = "content_locker",
 *   title = @Translation("Content locker"),
 *   description = @Translation("Hide content inside shortcode.")
 * )
 */
class ContentLockerShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $locker = \Drupal::service('content_locker');

    // Merge with default attributes.
    $attributes = $this->getAttributes(['type' => ''], $attributes);

    // Print locked content if type is empty or request is ajax.
    if ($locker->isVisibleContent($attributes['type'])) {
      $output = [
        '#markup' => FieldFilteredMarkup::create($text),
      ];

      return $this->render($output);
    }

    $output = [
      '#theme_wrappers' => ['content_locker'],
      '#plugin_type' => $attributes['type'],
      '#entity' => $locker->getCurrentEntity(),
      '#markup' => $locker->isDelayContent() ? NULL : FieldFilteredMarkup::create($text),
    ];

    return $this->render($output);
  }

  /**
   * {@inheritdoc}
   */
  public function render(&$element) {
    $renderer = \Drupal::service('renderer');
    return $renderer->render($element);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[content_locker]some text[/content_locker]') . '</strong> ';
    return implode(' ', $output);
  }

}
