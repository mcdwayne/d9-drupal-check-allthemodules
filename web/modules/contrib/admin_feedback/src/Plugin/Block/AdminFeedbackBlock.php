<?php

/**
 * @file
 * Code for the Admin Feedback Block.
 */

namespace Drupal\admin_feedback\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides the Admin Feedback Block.
 *
 * @Block(
 *   id = "admin_feedback_block",
 *   admin_label = @Translation("Admin Feedback Block"),
 * )
 */
class AdminFeedbackBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = array();
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node && !is_string($node)) {

      $question = t('Was this helpful?');
      $yes_answer = t('Yes');
      $no_answer = t('No');

      $build = [
        '#markup' =>
        '<div id="upper_feedback_content">
          <h2 class="feedback_webform_upper_text"><span></span>' . $question . '</h2>
          <span class="feedback-webform-button" id=yes/' . $node->id() . '>' . $yes_answer . '</span> 
          <span class="feedback-webform-button" id=no/' . $node->id() . '>' . $no_answer . '</span>
        </div>',
        'form' => \Drupal::formBuilder()->getForm('Drupal\admin_feedback\Form\AdminFeedbackAjaxForm'),
        '#prefix' => '<div id="feedback-message">',
        '#suffix' => '</div>',
        '#cache' => [
          'contexts' => [
            'url.path',
          ],
        ],
        '#attached' => [
          'library' => [
            'admin_feedback/admin_feedback_block',
            'admin_feedback/admin_feedback_css',
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
