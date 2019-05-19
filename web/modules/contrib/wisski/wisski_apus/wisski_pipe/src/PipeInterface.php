<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\PipeInterface.
 */

namespace Drupal\wisski_pipe;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Psr\Log\LoggerInterface;


interface PipeInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {
  

  /**
   * Returns the description of the pipe
   *
   * @return string
   */
  public function getDescription();
  

  /**
   * Sets the description for the pipe
   */
  public function setDescription($desc);
  

  /**
   * Returns the processors in execution order
   *
   * @return ProcessorInterface[]
   *  Each entry is a processor
   */
  public function getProcessors();


  
  /**
   * Returns an array of tags that help to categorize the processor.
   * 
   * This is the merged tags array of all processors within the pipe.
   *
   * @return array
   *  Array of string tags each as key and value pair. 
   */
  public function getTags();
  

  /**
   * Runs the pipe, i.e. calls ProcessorInterface::run() on every single processor
   * 
   * @param data
   *  The data to be processed. This may be any kind of data and data type,
   *  depending on the processors. Note that if this is an object, it may be
   *  altered.
   * @param ticket
   *  Optionally provide a ticket. This is a string like a UUID that should
   *  uniquely identify the ongoing process.
   * @param logger
   *  Optionally provide a logger that the pipe/processors can log to.
   *
   * @return object
   *  The processed data, this may but must not be the same data object as 
   *  the one input.
   */
  public function run($data, $ticket = '', LoggerInterface $logger = NULL);



}
 
