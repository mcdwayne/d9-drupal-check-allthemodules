<?php

namespace Drupal\bcs;

use Drupal\Core\Database\Database;

/**
 * Class BCSBase for block content select functions.
 */
class BCSBase {

  /**
   * Load list view modes.
   *
   * @return array
   *   The node view modes.
   */
  public static function bcsLoadViewModes(): array {
    $view_modes = [];

    $entity_info = \Drupal::entityManager()->getViewModes('node');
    if (!empty($entity_info)) {
      foreach ($entity_info as $k => $v) {

        $view_modes[$k] = $v['label'];
      }
    }

    return $view_modes;
  }

  /**
   * Save data block.
   *
   * @param string $id_block
   *   The block id.
   * @param array $items
   *   List of selected items.
   *
   * @throws \Exception
   */
  public static function bcsSaveDataBlock($id_block, array $items): void {
    $user = \Drupal::currentUser();

    $block = self::bcsLoadDataBlock($id_block);

    $fields = [
      'uid' => $user->id(),
      'id_block' => $id_block,
      'data' => @serialize($items),
      'status' => 1,
      'created' => REQUEST_TIME,
    ];

    if ($block) {
      // Update data block.
      db_update('bcs_data')
        ->fields($fields)
        ->condition('id_block', $id_block)
        ->execute();

    }
    else {
      // Insert data block.
      Database::getConnection()
        ->insert('bcs_data')
        ->fields($fields)
        ->execute();
    }
  }

  /**
   * Load data block by id block.
   *
   * @param string $id_block
   *   The block id.
   *
   * @return mixed
   *   The block content select data.
   */
  public static function bcsLoadDataBlock($id_block) {
    $result = db_select('bcs_data', 't')
      ->fields('t')
      ->condition('t.id_block', $id_block, '=')
      ->execute()
      ->fetchObject();

    return $result;
  }

  /**
   * Load data block by id block.
   *
   * @param string $id_block
   *   The block id.
   *
   * @return mixed
   *   The block content select data.
   */
  public static function bcsDeleteDataBlock($id_block): void {
    db_delete('bcs_data')
      ->condition('id_block', $id_block)
      ->execute();
  }

  /**
   * Load node view by id and mode view.
   *
   * @param int $nid
   *   The node id.
   * @param string $mode_view
   *   The view mode.
   *
   * @return array
   *   The node data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function bcsLoadNodeView($nid, $mode_view = 'teaser'): array {
    $entity = \Drupal::entityManager()
      ->getStorage('node')
      ->load($nid);

    $node_view = \Drupal::getContainer()
      ->get('entity.manager')
      ->getViewBuilder('node');

    $node_output = $node_view->view($entity, $mode_view);

    return $node_output;
  }

  /**
   * Load list content types.
   *
   * @return array
   *   The content types.
   */
  public static function loadContentTypesList(): array {
    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();

    $contentTypesList = [];
    foreach ($contentTypes as $contentType) {
      $contentTypesList[$contentType->id()] = $contentType->label();
    }

    return $contentTypesList;
  }

}
