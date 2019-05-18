<?php
/**
 * @file
 * Contains \Drupal\feedmine\Plugin\Block\FeedmineFeedbackBlock.
 */

namespace Drupal\feedmine\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides test block.
 *
 * @Block(
 *   id = "feedmine_feedback",
 *   admin_label = @Translation("Feedmine: Feedback submission"),
 *   category = @Translation("Blocks")
 * )
 */
class FeedmineFeedbackBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!\Drupal::currentUser()->hasPermission('feedmine submit feedback')) {
        $form = NULL;
    } else {
    $form = \Drupal::formBuilder()->getForm('Drupal\feedmine\Form\FeedmineBlockContentsForm');
    $form['#cache']['max-age'] = 0;
    $form['#attached'] = array(
        'library' =>  array(
          'feedmine/feedmine'
        ),
      );
    }
    return $form;
 }

}
