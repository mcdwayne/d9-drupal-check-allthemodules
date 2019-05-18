<?php

/**
 * @file
 * Definition of Drupal\grassroot_interests\GrassrootInterestManager.
 */

namespace Drupal\grassroot_interests;

use Drupal\Core\Database\Connection;

/**
 * Grassroot Interest manager.
 */
class GrassrootInterestManager implements GrassrootInterestManagerInterface {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a BookOutlineStorage object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function checkKeywords($url_id) {
    return (bool) $this->connection->query("SELECT * FROM {grassroot_interests_path_keyword} WHERE url_id = :url_id", array(':url_id' => $url_id))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getKeywordsByID($url_id) {
    $result = $this->connection->select('grassroot_interests_path_keyword', 'gipk')
      ->fields('gipk', array('kid', 'keyword', 'kw_title', 'root_url', 'url_id'))
      ->condition('url_id', $url_id, '=')
      ->execute()
      ->fetchAll();

    foreach ($result as $id => $data) {
      $keyword[] = $data->keyword;
    }
    $result_array = array();
    $result_array['title'] = t('Edit Keywords');
    $result_array['kw_title'] = isset($result[0]->kw_title) ? $result[0]->kw_title : "";
    $result_array['root_url'] = isset($result[0]->root_url) ? $result[0]->root_url : "";
    $result_array['url_id'] = isset($result[0]->url_id) ? $result[0]->url_id : "";
    $result_array['keyword'] = implode("\n", $keyword);
    return $result_array;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->connection->select('grassroot_interests_path_keyword', 'gipk')
      ->fields('gipk', array('kw_title', 'url_id', 'root_url'))
      ->distinct();
  }

  /**
   * {@inheritdoc}
   */
  public function saveKeywords($grassroot_data) {

    $title = $grassroot_data['title'];
    $root_url = $grassroot_data['root_url'];
    $keywords = $grassroot_data['keywords'];
    $url_id = $grassroot_data['url_id'];

    $values = array();
    foreach ($keywords as $keyword) {
      $values[] = array(
        'kw_title' => $title,
        'root_url' => $root_url,
        'keyword' => $keyword,
        'url_id' => $url_id,
      );
    }
    
    $query = $this->connection->insert('grassroot_interests_path_keyword')
      ->fields(array(
        'kw_title',
        'root_url',
        'keyword',
        'url_id',
      )
    );

    foreach ($values as $record) {
      $query->values($record);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeywords($url_id) {
    $this->connection->delete('grassroot_interests_path_keyword')
      ->condition('url_id', $url_id)
      ->execute();
  }

}
