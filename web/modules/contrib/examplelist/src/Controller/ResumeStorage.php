<?php

namespace Drupal\examplelist\Controller;

class ResumeStorage {

  static function getAll() {
    $limit = 5;
    if (empty($_REQUEST['page'])) {
      $start = 0;
    }
    else {
      $start = $_REQUEST['page'] * $limit;
    }
	 $header = array(
      'id' => array('data' => 'Id', 'field' => 'c.id','sort' => 'DESC'),
      'name' => array('data' => 'Candidade name', 'field' => 'c.candidate_name','sort' => 'DESC'),
      'email' => array('data' => 'Email', 'field' => 'c.candidate_mail','sort' => 'DESC'),
      'operations' => t('Delete'),
    );
	
	$db = \Drupal::database();
    $query = $db->select('examplelist','c');
    $query->fields('c');
    // The actual action of sorting the rows is here.
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
                        ->orderByHeader($header);
    // Limit the rows to 20 for each page.
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                        ->limit($limit);
    $result = $pager->execute();
    return $result;
  }

  static function exists($id) {
    return (bool) $this->get($id);
  }

  static function add($name, $message) {
    db_insert('examplelist')->fields(array(
      'name' => $name,
      'message' => $message,
    ))->execute();
  }

  static function delete($id) {
    db_delete('examplelist')->condition('id', $id)->execute();
  }
  static function get($id) {
	  
	$db = \Drupal::database();
    $query = $db->select('examplelist','c');
    $query->fields('c');
	 $query->condition('id',$id);
	 $result = $query->execute()->fetchObject();
	//print_r($query);die;
    return $result;
  }
}
