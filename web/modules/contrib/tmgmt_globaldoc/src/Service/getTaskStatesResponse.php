<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getTaskStatesResponse
{

  /**
   * 
   * @var GlobalDocStatesResponse $return
   * @access public
   */
  public $return;

  /**
   * 
   * @param GlobalDocStatesResponse $return
   * @access public
   */
  public function __construct($return)
  {
    $this->return = $return;
  }

}
