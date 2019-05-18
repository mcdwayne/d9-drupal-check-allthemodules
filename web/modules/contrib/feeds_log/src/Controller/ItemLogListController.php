<?php

namespace Drupal\feeds_log\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Processor\EntityProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lists the feed items belonging to a feed.
 */
class ItemLogListController extends ControllerBase {

  /**
   * Lists the feed items belonging to a feed.
   */
  public function listItems(FeedInterface $feeds_feed, Request $request) {
    $processor = $feeds_feed->getType()->getProcessor();

    $header = [
      'title' => $this->t('Label'),
      'imported' => $this->t('Log time'),
      'message' => $this->t('Message'),
    ];

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [],
      '#empty' => $this->t('There are no items yet.'),
    ];

    // @todo Allow processors to create their own entity listings.
    if (!$processor instanceof EntityProcessorInterface) {
      return $build;
    }

    $query = \Drupal::database()->select('feeds_log', 'fl')->fields('fl');
    $query->condition('fl.fid', $feeds_feed->id(), '=');
    $query->orderBy('fl.imported', 'DESC');
    $result = $query->execute()->fetchAll();
    foreach($result as $row => $value) {
      $ago = \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $value->imported);
      $row = [];
      // Entity link.
      $row[] = $this->t($value->label);
      // Imported ago.
      $row[] = $this->t('@time ago', ['@time' => $ago]);
      $row[] = $this->t($value->message);
      $build['table']['#rows'][] = $row;
    }

    $build['pager'] = ['#type' => 'pager'];
    $build['#title'] = $this->t('%title items', ['%title' => $feeds_feed->label()]);

    return $build;
  }

}
