<?php

namespace Drupal\empty_page\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Manage empty pages.
 */
class EmptyPageController {

  /**
   * List out the pages created via empty page.
   */
  public function emptyPageList() {
    $header = [
      t('INTERNAL PATH'),
      [
        'data' => t('Operations'),
        'colspan' => 2,
      ],
    ];
    $rows = [];
    $callbacks = self::emptyPageGetCallbacks();
    if (!empty($callbacks)) {
      foreach ($callbacks as $cid => $callback) {
        $view_url = Url::fromRoute('empty_page.page_' . $cid);
        $edit_url = Url::fromRoute('empty_page.edit_callback', ['cid' => $cid]);
        $delete_url = Url::fromRoute('empty_page.delete_callback', ['cid' => $cid]);
        $title = $callback['page_title'] ?: t('No title');
        $row    = [
          \Drupal::l($title, $view_url),
          Link::fromTextAndUrl(t('Edit'), $edit_url),
          Link::fromTextAndUrl(t('Delete'), $delete_url),
        ];
        $rows[] = $row;
      }
    }
    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No callbacks exist yet.'),
    ];
    $build = [
      '#type' => 'details',
      '#title' => 'Manage',
      '#children' => $table,
      '#open' => TRUE,
    ];
    return $build;
  }

  /**
   * Get all Empty Page callbacks.
   *
   * @return callbacks
   *   An Array of Callbacks.
   */
  public static function emptyPageGetCallbacks() {
    $settings = \Drupal::configFactory()->get('empty_page.settings')->getRawData();
    $results = [];

    foreach ($settings as $key => $val) {
      if (substr($key, 0, 9) == 'callback_') {
        $results[$val['cid']] = $val;
      }
    }
    return $results;
  }

}
