<?php

namespace Drupal\tmgmt_globaldoc\Service;

class GlobalDocStatesResponse
{

  /**
   * 
   * @var string $status
   * @access public
   */
  public $status;

  /**
   * 
   * @var string $taskID
   * @access public
   */
  public $taskID;

  /**
   * 
   * @param string $status
   * @param string $taskID
   * @access public
   */
  public function __construct($status, $taskID)
  {
    $this->status = $status;
    $this->taskID = $taskID;
  }

}
