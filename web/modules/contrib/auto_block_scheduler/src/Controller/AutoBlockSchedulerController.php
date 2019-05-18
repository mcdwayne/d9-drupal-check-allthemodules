<?php

namespace Drupal\auto_block_scheduler\Controller;

use Drupal\block\Entity\Block;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class AutoBlockSchedulerController.
 *
 * @package Drupal\AutoBlockScheduler\Controller
 */
class AutoBlockSchedulerController extends ControllerBase {

  /**
   * Scheduler block list.
   *
   * @return html
   *   The table result.
   */
  public function schedulerBlockList() {
    $blocks = Block::loadMultiple();

    $current_url = Url::fromRoute('<current>');
    $destination = $current_url->toString();

    $rows = [];
    $inc = 0;
    foreach ($blocks as $key => $block) {
      $settings = $block->get('visibility');
      $url = Url::fromRoute('entity.block.edit_form', [
        'block' => $key,
      ], ['query' => ['destination' => $destination], 'absolute' => TRUE]);

      if (isset($settings['auto_block_scheduler'])) {
        $rows[$inc]['id'] = $key;
        $rows[$inc]['theme'] = $block->get('theme');
        $rows[$inc]['region'] = $block->get('region');
        $rows[$inc]['status'] = (!empty($block->get('status'))) ? 'Enabled' : 'Disabled';
        $rows[$inc]['label'] = $block->get('settings')['label'];
        $rows[$inc]['published_on'] = (!empty($settings['auto_block_scheduler']['published_on'])) ? DrupalDateTime::createFromTimestamp($settings['auto_block_scheduler']['published_on']) : 'NA';
        $rows[$inc]['unpublished_on'] = (!empty($settings['auto_block_scheduler']['unpublished_on'])) ? DrupalDateTime::createFromTimestamp($settings['auto_block_scheduler']['unpublished_on']) : 'NA';
        $rows[$inc]['negate'] = (!empty($settings['auto_block_scheduler']['negate'])) ? '1' : '0';
        $rows[$inc]['configure'] = Link::fromTextAndUrl($this->t('Configure'), $url);
        $inc++;
      }
    }
    return [
      '#type' => 'table',
      '#header' => [
        'id' => 'Id',
        'theme' => 'Theme',
        'region' => 'Region',
        'status' => 'Status',
        'label' => 'Label',
        'published_on' => 'Published On',
        'unpublished_on' => 'Unpublished On',
        'negate' => 'Negate',
        'operation' => 'Operation',
      ],
      '#rows' => $rows,
      "#sticky" => TRUE,
      "#empty" => "No, Block scheduled with auto block scheduler",
      '#caption' => $this->t("Auto Block Scheduler"),
    ];
  }

}
