<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_menu"
 * )
 */
class Menu extends MigrationsSourceBase {
  public $src = 'menu-main';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    if ($source = $this->getContent($this->src)) {
      foreach ($source as $key => $row) {
        $id = $row['uuid'];
        $uri = "internal:{$row['uri']}";
        if (substr($row['uri'], 0, 5) == 'menu:' && db_table_exists('migrate_map_store_page')) {
          $query = \Drupal::database()->select('migrate_map_store_page', 'm');
          $query->condition('sourceid1', substr($row['uri'], 5));
          $query->fields('m', ['destid1']);
          $res = $query->execute()->fetchall();
          if ($res) {
            $dst = $res[0]->destid1;
            $uri = "entity:node/{$dst}";
          }
        }
        $parent = $row['parent'];
        if ($parent && !strpos($parent, '.') && db_table_exists('migrate_map_store_menu')) {
          $query = \Drupal::database()->select('migrate_map_store_menu', 'm');
          $query->condition('sourceid1', $parent);
          $query->fields('m', ['destid1']);
          $res = $query->execute()->fetchall();
          if ($res) {
            $parent = $res[0]->destid1;
            $query = \Drupal::database()->select('menu_link_content', 'm');
            $query->condition('id', $parent);
            $query->fields('m', ['uuid']);
            $res = $query->execute()->fetchall();
            if ($res) {
              $uuid = $res[0]->uuid;
              $parent = "menu_link_content:{$uuid}";
            }
          }
        }
        $rows[$id] = [
          'id' => $id,
          'bundle' => 'menu_link_content',
          'menu' => 'main',
          'title' => $row['name'],
          'uri' => $uri,
          'weight' => $row['weight'],
          'external' => FALSE,
          'expanded' => TRUE,
          'enabled' => TRUE,
          'parent' => $parent,
        ];
      }
    }
    $this->debug = FALSE;
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $source = $this->getContent($this->src, TRUE);
    return count($source);
  }

}
