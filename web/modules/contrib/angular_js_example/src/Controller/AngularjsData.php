<?php

namespace Drupal\angular_js_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * AngularjsData method.
 *
 * @package Drupal\angular_js_example\Controller
 */
class AngularjsData extends ControllerBase {

  /**
   * The book save data method.
   */
  public function angularSaveData() {
    $db = Database::getConnection('default');
    if (empty($_POST['bid'])) {
      $db->insert('angular_js_example_books')
        ->fields([
          'bookname' => $_POST['name'],
          'bookprice' => $_POST['price'],
          'authorid' => $_POST['authorId'],
        ])
        ->execute();
    }
    else {
      $db->update('angular_js_example_books')
        ->fields([
          'bookname' => $_POST['name'],
          'bookprice' => $_POST['price'],
          'authorid' => $_POST['authorId'],
        ])
        ->condition('bid', $_POST['bid'])
        ->execute();
    }
    return $this->angularGetData();
  }

  /**
   * The book get data method.
   */
  public function angularGetData() {
    $rows = [];
    $db = Database::getConnection('default');
    $query = $db->select('angular_js_example_books', 'b');
    $query->fields('b');
    $result = $query->execute()->fetchAll();
    foreach ($result as $key => $value) {
      $rows[$key]->bid = $value->bid;
      $rows[$key]->bookname = Html::escape($value->bookname);
      $rows[$key]->bookprice = Html::escape($value->bookprice);
      $rows[$key]->authorid = Html::escape($value->authorid);
    }
    return new JsonResponse($rows, 200, ['Content-Type' => 'application/json']);
  }

  /**
   * The book delete data method.
   */
  public function angularDeleteData() {
    $db = Database::getConnection('default');
    $query = $db->delete('angular_js_example_books');
    $query->condition('bid', $_POST['bid']);
    $query->execute();
    return $this->angularGetData();
  }

  /**
   * The book edit data method.
   */
  public function angularEditData($id) {
    $rows = [];
    $db = Database::getConnection('default');
    $query = $db->select('angular_js_example_books', 'b');
    $query->fields('b');
    $query->condition('bid', $id);
    $result = $query->execute()->fetchAll();
    foreach ($result as $value) {
      $rows['bid'] = $value->bid;
      $rows['name'] = $value->bookname;
      $rows['price'] = $value->bookprice;
      $rows['authorId'] = $value->authorid;
    }
    return new JsonResponse($rows, 200, ['Content-Type' => 'application/json']);
  }

}
