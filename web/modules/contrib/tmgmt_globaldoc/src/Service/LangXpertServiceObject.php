<?php

namespace Drupal\tmgmt_globaldoc\Service;

class LangXpertServiceObject
{

  /**
   * 
   * @var base64Binary $fileByte
   * @access public
   */
  public $fileByte;

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
   * @param base64Binary $fileByte
   * @param string $status
   * @param string $taskID
   * @access public
   */
  public function __construct($fileByte, $status, $taskID)
  {
    $this->fileByte = $fileByte;
    $this->status = $status;
    $this->taskID = $taskID;
  }

}
