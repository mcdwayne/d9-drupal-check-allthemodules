<?php

namespace Drupal\simple_feedback\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides the Simple Feedback Block.
 *
 * @Block(
 *   id = "simple_feedback_block",
 *   admin_label = @Translation("Simple Feedback Block"),
 * )
 */
class SimpleFeedbackBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = array();
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node && !is_string($node)) {
      $link_options = [
        'attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
      ];

      // Construct the "yes" link.
      $link_options['attributes']['aria-label'] = $this->t('Let us know that this content is helpful');
      $link_options['attributes']['title'] = $this->t('Helpful');
      $yes_url = Url::fromRoute('simple_feedback.callback', ['node' => $node->id(), 'feedback' => 'yes'], $link_options);
      $yes = Link::fromTextAndUrl('Yes', $yes_url);
      $yes = $yes->toRenderable();

      // Construct the "no" link.
      $link_options['attributes']['aria-label'] = $this->t('Let us know that this content is not helpful');
      $link_options['attributes']['title'] = $this->t('Not helpful');
      $no_url = Url::fromRoute('simple_feedback.callback', ['node' => $node->id(), 'feedback' => 'no'], $link_options);
      $no = Link::fromTextAndUrl('No', $no_url);
      $no = $no->toRenderable();

      $content_type = $node->type->entity->label();
      $args = [
        ':content_type' => strtolower($content_type),
        '@yes' => render($yes),
        '@no' => render($no),
      ];

      $output = [
        '#markup' => '<p>' . $this->t('Was this :content_type helpful? @yes <span id="yes-vote"></span> | @no <span id="no-vote"></span>', $args) . '</p>',
        '#prefix' => '<div id="feedback-message">',
        '#suffix' => '</div>',
        '#cache' => [
          'contexts' => [
            'url.path',
          ],
        ],
        '#attached' => [
          'library' => [
            'simple_feedback/simple_feedback_block',
          ],
        ],
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }
}
