<?php

namespace Drupal\examplelist\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * AngularjsData method.
 *
 * @package Drupal\examplelist\Controller
 */
class Angularlist extends ControllerBase {
	
	
	public function content() {
	  global $base_url;
	//return array(  '#markup' => "<p>".$internal_link."</p>".drupal_render($table),);
	
	$events = "My Custom events";
        return [
        	'#theme' => 'angularlist_form',
        	'#events' => $events,
			'#attached' =>
			array(
			  'library' =>
				array('examplelist/angular.angularjslist'),
			  'drupalSettings' => array('angular_js_example'=>array('url_base'=>$base_url))
			),
        ];

  
  }

  /**
   * The book save data method.
   */
  public function angularSaveData() {
    $db = Database::getConnection('default');
    if (empty($_POST['bid'])) {
      $db->insert('examplelist')
        ->fields([
          'candidate_name' => $_POST['name'],
          'candidate_mail' => $_POST['price'],
          'candidate_number' => $_POST['authorId'],
		  'candidate_dob' =>'03-07-1985',
		  'candidate_gender' =>'Male',
		  'candidate_confirmation'=>'Yes',
		  'candidate_copy'=>'yes',
        ])
        ->execute();
    }
    else {
      $db->update('examplelist')
        ->fields([
          'candidate_name' => $_POST['name'],
          'candidate_mail' => $_POST['price'],
          'candidate_number' => $_POST['authorId'],
		  'candidate_dob' =>'03-07-1985',
		  'candidate_gender' =>'Male',
		  'candidate_confirmation'=>'Yes',
		  'candidate_copy'=>'yes',
        ])
        ->condition('id', $_POST['bid'])
        ->execute();
    }
    return $this->angularGetData();
  }

  /**
   * The book get data method.
   */
  public function angularGetData() {
	 
	$limit = 15;
    if (empty($_REQUEST['page'])) {
      $start = 0;
    }
    else {
      $start = $_REQUEST['page'] * $limit;
    }
	  
    $rows = [];
    $db = Database::getConnection('default');
    $query = $db->select('examplelist', 'b');
    $query->fields('b');
    //$result = $query->execute()->fetchAll();
	$pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
                        ->limit($limit);
    $result = $pager->execute();
    foreach ($result as $key => $value) {
      $rows[$key]->bid = $value->id;
      $rows[$key]->bookname = Html::escape($value->candidate_name);
      $rows[$key]->bookprice = Html::escape($value->candidate_mail);
      $rows[$key]->authorid = Html::escape($value->candidate_number);
    }
	
    return new JsonResponse($rows, 200, ['Content-Type' => 'application/json']);
  }

  /**
   * The book delete data method.
   */
  public function angularDeleteData() {
    $db = Database::getConnection('default');
    $query = $db->delete('examplelist');
    $query->condition('id', $_POST['bid']);
    $query->execute();
    return $this->angularGetData();
  }

  /**
   * The book edit data method.
   */
  public function angularEditData($id) {
    $rows = [];
    $db = Database::getConnection('default');
    $query = $db->select('examplelist', 'b');
    $query->fields('b');
    $query->condition('id', $id);
    $result = $query->execute()->fetchAll();
    foreach ($result as $value) {
      $rows['bid'] = $value->id;
      $rows['name'] = $value->candidate_name;
      $rows['price'] = $value->candidate_mail;
      $rows['authorId'] = $value->candidate_number;
    }
    return new JsonResponse($rows, 200, ['Content-Type' => 'application/json']);
  }

}
