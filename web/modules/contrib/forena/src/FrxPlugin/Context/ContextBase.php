<?php

/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/26/16
 * Time: 6:34 AM
 */

namespace Drupal\forena\FrxPlugin\Context; 

use Drupal\forena\Context\DataContext;

abstract class ContextBase implements ContextInterface {
  
  /**
   * Return the properteis of the element. 
   * @return null|\SimpleXMLElement
   */
  public function asXML() {
    return DataContext::arrayToXml(get_object_vars($this)); 
  }
}