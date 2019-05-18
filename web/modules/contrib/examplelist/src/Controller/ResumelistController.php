<?php
/**
 * @file
 * Contains \Drupal\first_module\Controller\ResumeController.
 */

namespace Drupal\examplelist\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;

class ResumelistController extends ControllerBase {
  public function content() {
	  //$internal_link= Drupal::l(t('New message'), 'admin/content/bd_contact/add');
	  $url = Url::fromRoute('resume.form');
	  $internal_link = \Drupal\Core\Link::createFromRoute(t('Add New'), 'resume.form')->toString();


    $add_link = '<p></p>';

    // Table header.
    $header = array(
      'id' => array('data' => 'Id', 'field' => 'c.id','sort' => 'DESC'),
      'name' => array('data' => 'Candidade name', 'field' => 'c.candidate_name','sort' => 'DESC'),
      'email' => array('data' => 'Email', 'field' => 'c.candidate_mail','sort' => 'DESC'),
      'edit' => t('Edit'),
	  'operations' => t('Delete'),
    );

    $rows = array();

    foreach (ResumeStorage::getAll() as $id => $content) {
      // Row with attributes on the row and some of its cells.
	
	
	  $internal_link_delete = \Drupal\Core\Link::createFromRoute(t('Delete'), 'delete.form',['id'=>$content->id])->toString();
	  
	  $internal_link_edit = \Drupal\Core\Link::createFromRoute(t('edit'), 'edit.form',['id'=>$content->id])->toString();
	  
      $rows[] = array(
        'data' => array($content->id,$content->candidate_name,$content->candidate_mail,$internal_link_edit,$internal_link_delete),
      );
    }
	//print_r($rows);die;

    $table['config_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'bd-contact-table',
      ),
	 
    );
	 $table['pager'] = array(
      '#type' => 'pager'
    );
  //$table[] = ['#type' => 'pager'];
   // return $add_link.drupal_render($table);
	return array(  '#markup' => "<p>".$internal_link."</p>".drupal_render($table),);

  
  }
}