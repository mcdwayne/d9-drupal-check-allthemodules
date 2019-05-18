<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getTaskStateResponse
{

  /**
   * 
   * @var string $return
   * @access public
   */
  public $return;

  /**
   * 
   * @param string $return
   * @access public
   */
  public function __construct($return)
  {
    $this->return = $return;
  }

}
