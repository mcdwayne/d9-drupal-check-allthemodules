<?php

namespace Drupal\exam_spider\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\exam_spider\Controller\ExamSpider;

/**
 * Provides a block with a Exam lists.
 *
 * @Block(
 *   id = "exam_spider_block",
 *   admin_label = @Translation("Exam lists"),
 * )
 */
class ExamSpiderBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output[]['#cache']['max-age'] = 0;
    $examspider_service = new ExamSpider();
    $output[] = $examspider_service->examSpiderExamStart();
    return $output;
  }

}
