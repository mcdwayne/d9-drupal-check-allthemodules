<?php

namespace Drupal\drd\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Abstract class for widget blocks being used on the main dashboard.
 */
abstract class WidgetBase extends Base {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['drd_widget_title']['#markup'] = '<h2>' . $this->title() . '</h2>';
    $content = $this->content();
    if ($content instanceof FormattableMarkup || is_string($content)) {
      $build['drd_widget']['#markup'] = $content;
    }
    else {
      $build['drd_widget'] = $content;
    }
    return $build;
  }

  /**
   * Get the title of the widget.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title.
   */
  abstract protected function title();

  /**
   * Get the content of the widget.
   *
   * @return array|string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The content.
   */
  abstract protected function content();

}
