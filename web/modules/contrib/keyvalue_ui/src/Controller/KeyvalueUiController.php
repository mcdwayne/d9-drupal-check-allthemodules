<?php

namespace Drupal\keyvalue_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class KeyvalueUiController extends ControllerBase {

  /**
   * @return array
   */
  public function collections() {
    $build['header']['#markup'] = t("<h3>Warning! Only use if you know what you are doing, it's very easy to break your site via this UI!</h3>");
    $build['collections'] = [
      '#theme' => 'item_list',
      '#items' => array_map([$this, 'getLink'], \Drupal::database()->query('SELECT DISTINCT collection FROM {key_value} ORDER BY collection')->fetchCol()),
    ];
    return $build;
  }

  /**
   * @param $collection
   *
   * @return array
   */
  public function details($collection) {
    $build['header'] = [
      '#type' => 'inline_template',
      '#template' => '<h3>Collection: {{ collection }}</h3>',
      '#context' => ['collection' => $collection],
    ];
    $build['table'] = [
      // If we wanted to make the table header "sticky" this would need to be
      // #type but since this is not a form and we do not need "sticky", just
      // #theme will do.
      '#theme' => 'table',
      '#header' => [t('Name'), t('Value'), t('Edit'), t('Delete')],
      '#rows' => array_map([$this, 'getRow'], \Drupal::database()->query('SELECT collection, name, LEFT(value, 1) AS type FROM {key_value} WHERE collection = :collection ORDER BY name', [':collection' => $collection])->fetchAll(\PDO::FETCH_ASSOC)),
    ];
    $build['back'] = [
      '#type' => 'link',
      '#title' => t('Back'),
      '#url' => Url::fromRoute('keyvalue_ui.collections'),
    ];
    return $build;
  }

  /**
   * @param $collection
   *
   * @return array
   */
  protected function getLink($collection) {
    return [
      '#type' => 'link',
      '#title' => $collection,
      '#url' => Url::fromRoute('keyvalue_ui.details', ['collection' => $collection]),
    ];
  }

  /**
   * @param $data
   *   Associative array with two keys, name and collection.
   *
   * @return array
   */
  public function getRow($data) {
    if (in_array($data['type'], ['d', 's', 'i', 'b'])) {
      $data['value'] = \Drupal::keyValue($data['collection'])->get($data['name']);
      $edit = [
        '#type' => 'link',
        '#title' => 'Edit',
        '#url' => Url::fromRoute('keyvalue_ui.form', ['collection' => $data['collection'], 'name' => $data['name']]),
      ];
    }
    else {
      $data['value'] = ['a' => 'array', 'O' => 'object', 'N' => 'NULL'][$data['type']];
      $edit = ' ';
    }
    unset($data['type']);
    $delete = [
      '#type' => 'link',
      '#title' => 'Delete',
      '#url' => Url::fromRoute('keyvalue_ui.delete', $data),
    ];
    // Render arrays only work as cells in a ['data' => $array] format.
    return [$data['name'], $data['value'], ['data' => $edit], ['data' => $delete]];
  }

}
