<?php

namespace Drupal\angularjs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\node\Entity\Node;


/**
 * AngularjsData method.
 *
 * @package Drupal\angularjs\Controller
 */
class Angularlist extends ControllerBase {
	
	
	public function content() {
	  global $base_url;
	//return array(  '#markup' => "<p>".$internal_link."</p>".drupal_render($table),);
	
	$events = "My Custom events";
        return [
        	'#theme' => 'angularjslist_form',
        	'#events' => $events,
			'#attached' =>
			array(
			  'library' =>
				array('angularjs/angular.angularjslist'),
			  'drupalSettings' => array('angular_js_example'=>array('url_base'=>$base_url))
			),
        ];

  
  }

  /**
   * The book save data method.
   */
  public function angularSaveData() {
    
	
	
    if(!empty($_POST['nid'])){
		$node = Node::load($_POST['nid']);
		
			  
				$node->set('title', $_POST['name']);
				$node->set('field_candidate_email', $_POST['mail']);
				$node->set('field_phone_number', $_POST['phone']);
				$node->save();
			  
		
		
	}else{
		
		$my_article = Node::create(['type' => 'candidate']);
		$my_article->set('title', $_POST['name']);
		$my_article->set('field_candidate_email', $_POST['mail']);
		$my_article->set('field_phone_number', $_POST['phone']);
		
		$my_article->enforceIsNew();
		$my_article->save();
	}
    
	return array('#markup' => "true1111");
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
    if(!empty($_POST['nid'])){
				
		$result = \Drupal::entityQuery('node')
		->condition('type', 'candidate')
		->condition('nid', $_POST['nid'])
		->execute();
		entity_delete_multiple('node', $result);
		return array('#markup' => "true1111");
	}
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
