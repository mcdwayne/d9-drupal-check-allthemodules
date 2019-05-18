<?php

namespace Drupal\vk_crosspost\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;



class debugPage extends ControllerBase {


  public function helloWorld() {
    $output = array();

    $output['#title']  = 'HelloWorld page title';
    $output['#markup'] = 
'<p>' . 'static my_content = ' . $my_content . '</p>' ;
 

var_dump($check_bool);
 
    return $output;
  }




}
