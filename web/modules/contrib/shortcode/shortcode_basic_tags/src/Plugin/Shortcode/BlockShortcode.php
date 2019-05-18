<?php

namespace Drupal\shortcode_basic_tags\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Insert div or span around the text with some css classes.
 *
 * @Shortcode(
 *   id = "block",
 *   title = @Translation("Block"),
 *   description = @Translation("Insert a block.")
 * )
 */
class BlockShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'id' => '',
      'view' => 'full',
    ],
      $attributes
    );

    if (intval($attributes['id'])) {
      $block_entity = BlockContent::load($attributes['id']);
      if ($block_entity) {
        $block_view = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block_entity, $attributes['view']);
        if ($block_view) {
          return drupal_render($block_view);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[block id="1" (view="full") /]') . '</strong>';
    $output[] = $this->t('Inserts a block.') . '</p>';
    if ($long) {
      $output[] = '<p>' . $this->t('The block display view can be specified using the <em>view</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
