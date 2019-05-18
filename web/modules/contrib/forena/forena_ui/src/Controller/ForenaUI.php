<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/7/16
 * Time: 7:57 PM
 */

namespace Drupal\forena_ui\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\forena\FrxAPI;

class ForenaUI extends ControllerBase{

  use FrxAPI;
  public function reports() {
    $this->reportFileSystem()->allReports(); 
    $build = [];
    return $build; 
  }
}