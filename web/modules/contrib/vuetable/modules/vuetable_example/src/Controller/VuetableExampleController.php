<?php

/**
 * @file
 * Contains \Drupal\vuetable_example\Controller\VuetableExampleController.
 */

namespace Drupal\vuetable_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for theme example routes.
 */
class VuetableExampleController extends ControllerBase {

  public function simple() {
    
    $element =  array();
    
    $element['vuetable_element']['#type'] = 'vuetable_element';
    $element['vuetable_element']['#api_url'] = 'https://vuetable.ratiw.net/api/users';
    $element['vuetable_element']['#fields']=  array(
      ['name' => 'name', 'sortField' => 'name'],
        'email',
      [ 'name' => 'birthdate', 'sortField' => 'birthdate', 'titleClass' => 'center aligned', 'dataClass' => 'center aligned',]
        ,'nickname',
        'gender',
        '__slot:actions'
    );
    
    //If this vuetable is fetching data from REST exported by a Drupal view
    //We need to overwrite following parameters sent the Drupal view
    /*
    $element['vuetable_element']['#order_param'] = 'sort_order';
    $element['vuetable_element']['#page'] = 'page';
    $element['vuetable_element']['#sort'] = 'sort_by';
    $element['vuetable_element']['#start_page'] = 0;
    */
    
    return $element;
  }
}