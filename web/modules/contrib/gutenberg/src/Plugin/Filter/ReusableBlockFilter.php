<?php

namespace Drupal\gutenberg\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\block_content\Entity\BlockContent;

/**
 * @Filter(
 *   id = "filter_reusable_block",
 *   title = @Translation("Gutenberg Reusable Block filter"),
 *   description = @Translation("Renders Gutenberg reusable blocks."),
 *   settings = {
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class ReusableBlockFilter extends FilterBase {

  /**
   * Process each block.
   */
  public function process($text, $langcode) {
    $lines = explode("\n", $text);

    $lines = preg_replace_callback('#^<!-- wp:block.*\s(.*)\s\/-->$#', [$this, 'renderBlock'], $lines);

    $text = implode("\n", $lines);

    return new FilterProcessResult($text);
  }

  /**
   * Callback function to process each block.
   */
  private function renderBlock($match) {
    $comment = $match[0];
    $attributes = json_decode($match[1]);
    $bid = $attributes->ref;
    $block = BlockContent::load($bid);

    if (!$block) {
      return $comment;
    }

    $render = \Drupal::entityTypeManager()
      ->getViewBuilder('block_content')->view($block, 'reusable_block');

    $content = \Drupal::service('renderer')->render($render);
    return $comment . $content;
  }

}
