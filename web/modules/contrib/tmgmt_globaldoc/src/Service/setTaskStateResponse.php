<?php

namespace Drupal\tmgmt_globaldoc\Service;

class setTaskStateResponse
{

  /**
   * 
   * @var boolean $return
   * @access public
   */
  public $return;

  /**
   * 
   * @param boolean $return
   * @access public
   */
  public function __construct($return)
  {
    $this->return = $return;
  }

}
