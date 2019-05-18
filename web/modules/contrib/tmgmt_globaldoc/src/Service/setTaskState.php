<?php

namespace Drupal\tmgmt_globaldoc\Service;

class setTaskState
{

  /**
   * 
   * @var string $businessUnit
   * @access public
   */
  public $businessUnit;

  /**
   * 
   * @var string $taskId
   * @access public
   */
  public $taskId;

  /**
   * 
   * @var string $translationStatus
   * @access public
   */
  public $translationStatus;

  /**
   * 
   * @var string $message
   * @access public
   */
  public $message;

  /**
   * 
   * @param string $businessUnit
   * @param string $taskId
   * @param string $translationStatus
   * @param string $message
   * @access public
   */
  public function __construct($businessUnit, $taskId, $translationStatus, $message)
  {
    $this->businessUnit = $businessUnit;
    $this->taskId = $taskId;
    $this->translationStatus = $translationStatus;
    $this->message = $message;
  }

}
