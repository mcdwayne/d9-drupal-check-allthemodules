<?php

namespace Drupal\funnel\Controller;

/**
 * @file
 * Contains \Drupal\funnel\Controller\Page.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for page example routes.
 */
class PageFunnels extends ControllerBase {

  /**
   * Page Callback.
   */
  public function page() {
    $vocabs = Helpers::vocabs();
    $list = [];
    if ($vocabs) {
      foreach ($vocabs as $vid => $vocabulary) {
        $url = Url::fromRoute('funnel.vocab', ['vid' => $vid]);
        $name = $vocabulary->get('name');
        $list[] = \Drupal::l($name, $url);
      }
    }
    return [
      'list' => [
        '#theme' => 'item_list',
        '#items' => $list,
        '#title' => $this->t('Vocabularies'),
      ],
    ];
  }

}
