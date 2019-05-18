<?php

namespace Drupal\funnel\Controller;

/**
 * @file
 * Contains \Drupal\funnel\Controller\Page.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

/**
 * Controller routines for page example routes.
 */
class PageFunnel extends ControllerBase {

  /**
   * Page Vocab.
   */
  public function vocab($vid, $tid = 0) {
    $vocab = FALSE;
    $data = [
      'settings' => [
        'funnel' => $vid,
        'tid' => $tid,
        'attach' => 'kanban',
        'updUrl' => "/funnel/{$vid}/{$tid}/update",
      ],
      'users' => Helpers::getUsers(),
    ];
    $list = [];
    if ($vocabs = Helpers::vocabs($vid)) {
      $vocab = $vocabs[$vid];
    }

    foreach (Helpers::loadNodes() as $key => $node) {
      $upd = format_date($node->changed->value, 'custom', 'dM H:i:s');
      $data['nodes'][] = [
        'id' => $key,
        'state' => 'vid-funnel',
        'label' => $node->title->value . " $upd",
        'tags' => Helpers::randTags(),
        'hex' => Helpers::randHex(),
        'resourceId' => Helpers::randUser(),
      ];
    }

    if ($vocab) {
      $storage = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term");
      $tree = $storage->loadTree($vid, $tid, 1);
      if ($tid) {
        $data['colums'][] = [
          'text' => Term::load($tid)->name->value,
          'dataField' => "tid-{$tid}",
        ];
      }
      else {
        $data['colums'][] = [
          'text' => $vocab->get('name'),
          'dataField' => "vid-{$vid}",
        ];
      }
      if (!empty($tree)) {
        foreach ($tree as $term) {
          $url = Url::fromRoute('funnel.term', ['vid' => $vid, 'tid' => $term->tid]);
          $name = $term->name;
          $list[] = \Drupal::l($name, $url);
          $data['colums'][] = [
            'text' => $name,
            'dataField' => 'tid-' . $term->tid,
          ];
        }
      }
    }
    return [
      '#attached' => [
        'library' => ['funnel/funnel.kanban'],
        'drupalSettings' => [
          'funnel' => $data,
        ],
      ],
      'funnel' => ['#markup' => '<div id="kanban"></div>'],
      'list' => [
        '#theme' => 'item_list',
        '#items' => $list,
        '#title' => $this->t('Taxonomy terms'),
      ],
    ];
  }

  /**
   * Title Callback.
   */
  public function title($vid, $tid = 0) {
    $name = ['Funnels'];
    if ($vocabs = Helpers::vocabs($vid)) {
      $name[] = $vocabs[$vid]->get('name');
    }
    if ($tid && $term = Term::load($tid)) {
      $name[] = $term->name->value;
    }
    return implode(" > ", $name);
  }

}
