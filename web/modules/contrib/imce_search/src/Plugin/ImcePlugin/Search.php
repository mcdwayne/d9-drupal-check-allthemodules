<?php

namespace Drupal\imce_search_plugin\Plugin\ImcePlugin;

use Drupal\imce\Imce;
use Drupal\imce\ImceFM;
use Drupal\imce\ImcePluginBase;

/**
 * Defines Imce Search plugin.
 *
 * @ImcePlugin(
 *   id = "search",
 *   label = "Search",
 *   operations = {
 *     "search" = "opSearch"
 *   }
 * )
 */
class Search extends ImcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildPage(array &$page, ImceFM $fm) {
    $page['#attached']['library'][] = 'imce_search_plugin/drupal.imce.search';
  }

  /**
   * Operation handler: rename.
   */
  public function opSearch(ImceFM $fm) {
    $keywords = $fm->request->request->all('keywords')['keywords'];
    $query = "SELECT file_managed.fid AS fid, file_managed.langcode AS file_managed_langcode, file_managed.filename AS file_managed_filename, file_managed.filemime AS file_managed_filemime, file_managed.filesize AS file_managed_filesize, file_managed.status AS file_managed_status, file_managed.created AS file_managed_created, file_managed.changed AS file_managed_changed, SUM(file_usage_file_managed.count) AS file_usage_file_managed_count, MIN(file_managed.fid) AS fid_1
    FROM
    file_managed file_managed
    LEFT JOIN file_usage file_usage_file_managed ON file_managed.fid = file_usage_file_managed.fid
    WHERE file_managed.filename LIKE '%$keywords%'
    GROUP BY file_managed.fid, file_managed_langcode, file_managed_filename, file_managed_filemime, file_managed_filesize, file_managed_status, file_managed_created, file_managed_changed
    ORDER BY file_managed_changed DESC
    LIMIT 51 OFFSET 0";

    $result = db_query($query);
    if ($result) {
      $files = [];
      foreach($result as $item) {
        $temp = [];
        $filename = $item->file_managed_filename;
        $file = \Drupal\file\Entity\File::load($item->fid);
        $path = $file->url();
        $temp['filename'] = $filename;
        $temp['url'] = $path;
        $files[] = $temp;
      }
    }
    $fm->addResponse('data', $files);
  }

}
