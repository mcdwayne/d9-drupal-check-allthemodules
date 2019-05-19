<?php

namespace Drupal\webform_feedback\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'FeedbackBlock' block.
 *
 * @Block(
 *  id = "webform_feedback_block",
 *  admin_label = @Translation("Webform Feedback Block"),
 * )
 */
class FeedbackBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $webform_feedback_title = \Drupal::config('webform_feedback.webform_feedback_form')->get('webform_feedback_text');
    $webform_feedback_url = \Drupal::config('webform_feedback.webform_feedback_form')->get('webform_feedback');
    $webform_feedback_position = \Drupal::config('webform_feedback.webform_feedback_form')->get('webform_feedback_position');
    $options = ['absolute' => TRUE];
    $options = [
      'attributes' => [
        'class' => ['edit-button use-ajax'],
        'data-dialog-type' => 'modal',
      ],
    ];
    $webform_feedback = Link::fromTextAndUrl($webform_feedback_title, Url::fromUri('internal:/node/' . $webform_feedback_url, $options))->toString();
    $build = [];
    $build['webform_feedback_block']['#attached']['library'][] = 'webform_feedback/webform_feedback.styles';
    $build['#attributes']['class'][] = 'webform-position-' . $webform_feedback_position;
    $build['webform_feedback_block']['#markup'] = $webform_feedback;

    return $build;
  }

}
