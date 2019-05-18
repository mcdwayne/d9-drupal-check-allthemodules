<?php

namespace Drupal\tmgmt_globaldoc\Service;

class getTaskStates
{

  /**
   * 
   * @var string $requestorID
   * @access public
   */
  public $requestorID;

  /**
   * 
   * @param string $requestorID
   * @access public
   */
  public function __construct($requestorID)
  {
    $this->requestorID = $requestorID;
  }

}
