<?php

namespace Drupal\splash_screen\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Class SplashScreenController.
 *
 * @package Drupal\splash_screen\Controller
 */
class SplashScreenController extends ControllerBase {

  
  public function getContent() {
    // First we'll tell the user what's going on. This content can be found
    // in the twig template file: templates/description.html.twig.
    // @todo: Set up links to create nodes and point to devel module.
    $build = [
      'description' => [
        '#theme' => 'splash_screen_description',
        '#description' => 'foo',
        '#attributes' => [],
      ],
    ];
    return $build;
  }

  /**
   * Display.
   *
   * @return string
   *   Return Hello string.
   */
  public function display() {
    /**return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: display with parameter(s): $name'),
    ];*/

    //create table header
    $header_table = array(
     'id'=>    t('SrNo'),
      'name' => t('Name'),                
        'lang' => t('Language'),        
        'opt' => t('operations'),
        'opt1' => t('operations'),
    );

//select records from table
    $query = \Drupal::database()->select('splash_screen', 's');
      $query->fields('s', ['oid','name','lang']);
      $results = $query->execute()->fetchAll();
        $rows=array();
    foreach($results as $data){
        $delete = Url::fromUserInput('/admin/content/splash-screen/delete/'.$data->oid);
        $edit   = Url::fromUserInput('/admin/content/splash-screen/add?num='.$data->oid);

      //print the data from table
             $rows[] = array(
            'id' =>$data->oid,
                'name' => $data->name,
                'lang' => $data->lang,               
                 \Drupal::l('Delete', $delete),
                 \Drupal::l('Edit', $edit),
            );

    }
    //display data in site
    $form['table'] = [
            '#type' => 'table',
            '#header' => $header_table,
            '#rows' => $rows,
            '#empty' => t('No users found'),
        ];
//        echo '<pre>';print_r($form['table']);exit;
        return $form;

  }

}
