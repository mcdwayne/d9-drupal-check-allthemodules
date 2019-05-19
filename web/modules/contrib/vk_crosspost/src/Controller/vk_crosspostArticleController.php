<?php
 /**
  * @file
  * Contains \Drupal\vk_crosspost\Controller\vk_crosspostArticleController.
  */


namespace Drupal\vk_crosspost\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;





/**
 * 
 */
class vk_crosspostArticleController extends ControllerBase {

  /**
   * 
   * this page for debug module
   */
  public function helloWorld() {
    $output = array();

    $output['#title'] = 'HelloWorld page title';
   




$my_content = $_SESSION['my_content'];
$nid = $_SESSION['nid'];
$site = $_SESSION['site'];
$check_bool = $_SESSION['check_bool'];
$z = $_SESSION['z'];
$title_myCustomField = $_SESSION['title_myCustomField'];

if($my_content == null){
$my_content = 'null';
} 

if($site == null){
$site = 'null';
}
 
if($nid == null){
$nid = 'null';
}

$output['#markup'] = 
'<p>' . 'static my_content = ' . $my_content . '</p>' .
'<p>' . 'nid = ' . $nid . '</p>' .
'<p>' . 'site =' . $site . '</p>' .
'<p>' . 'check_bool =' . $z . '</p>' .
'<p>' . 'title_myCustomField' . $title_myCustomField . '</p>'; 

var_dump($check_bool);
 
    return $output;
  }




}
