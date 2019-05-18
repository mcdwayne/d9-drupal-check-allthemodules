<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getPDFTaskResponse
{

  /**
   * 
   * @var LangXpertServiceObject $return
   * @access public
   */
  public $return;

  /**
   * 
   * @param LangXpertServiceObject $return
   * @access public
   */
  public function __construct($return)
  {
    $this->return = $return;
  }

}
