<?php

namespace Drupal\stacks\WidgetAdmin\Button;

use Drupal\Core\Url;

/**
 * Class StepTwoPreviousButton
 * @package Drupal\stacks\WidgetAdmin\Button
 */
class StepCancelButton extends BaseButton {

  /**
   * @inheritDoc.
   */
  public function getKey() {
    return 'cancel';
  }

  /**
   * @inheritDoc.
   */
  public function build() {
    $delta = isset($_GET['delta']) ? $_GET['delta'] : 0;
    $link_options = ['query' => ['delta' => $delta]];
    return [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('stacks.admin.ajax_cancel', [], $link_options),
      '#attributes' => [
        'class' => ['use-ajax', 'link--gray'],
      ],
    ];
  }

}
