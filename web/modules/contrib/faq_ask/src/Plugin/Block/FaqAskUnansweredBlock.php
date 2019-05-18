<?php

namespace Drupal\faq_ask\Plugin\Block;

/**
 * @file
 * Contains \Drupal\faq_ask\Plugin\Block\FaqAskUnansweredBlock.
 */

use Drupal\faq_ask\Utility;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "faq_ask_unanswered",
 *   admin_label = @Translation("FAQ Unanswered Question")
 * )
 */
class FaqAskUnansweredBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#markup' => Utility::faqAskListUnanswered(10),
    );
  }

}
