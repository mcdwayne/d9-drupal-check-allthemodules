<?php

namespace Drupal\stacks\TwigExtension;

use Drupal\block\Entity\Block;
use Drupal\views\Views;

/**
 * Class BlockEmbed.
 * @package Drupal\stacks\TwigExtension
 */
class BlockEmbed extends \Twig_Extension {

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('block_embed', [$this, 'block_embed']),
    ];
  }

  public function getName() {
    return 'block_embed';
  }

  public function block_embed($block_id, $type = 'block') {
    $output = t("The content is not available");

    if ($type == 'block') {
      // Getting the block from their machine name.
      $block = Block::load($block_id);

      if (isset($block)) {
        $block_body = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block);

        // Rendering block output.
        $output = \Drupal::service('renderer')->render($block_body);
      }
    }
    else {
      // Getting the block based in view id and display id.
      $view_id = $type;
      $view_block = views_embed_view($view_id, $block_id);

      // Getting the view title.
      $view = Views::getView($view_id);

      if (isset($view)) {
        // Rendering block output.

        $output = [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'contextual-region',
              'block',
              'block-block-content',
              'block-block-' . $view_id,
            ],
            'id' => 'block-' . $view_id,
          ],
          'h2' => [
            '#type' => 'html_tag',
            '#tag' => 'h2',
            '#value' => $view->getTitle(),
          ],
          'view' => $view_block,
        ];

        $output = \Drupal::service('renderer')->render($output);
      }
    }

    print $output;
  }

}
