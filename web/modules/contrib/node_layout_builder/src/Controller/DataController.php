<?php

namespace Drupal\node_layout_builder\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class DataController for functions import export of data element.
 */
class DataController extends ControllerBase {

  /**
   * Import data element.
   *
   * @param int $nid
   *   ID of entity element.
   *
   * @return array
   *   Markup
   */
  public function import($nid) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('An error import builder'),
    ];
  }

  /**
   * Export data element.
   *
   * @param int $nid
   *   ID of entity element.
   */
  public function export($nid = 0) {
    dump($nid);die;
  }

}
