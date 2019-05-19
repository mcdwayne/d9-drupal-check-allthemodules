<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\ProcessorInterface.
 */

namespace Drupal\wisski_pipe;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for processor plugins.
 *
 * @see \Drupal\wisski_pipe\ProcessorManager
 * @see plugin_api
 */
interface ProcessorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {
  
  const FIELD_MANDATORY = 1;
  const FIELD_FACULTATIVE = 2;

  /**
   * Returns the processor label as defined in the annotation.
   *
   * @return string
   */
  public function getLabel();


  /**
   * Returns the processor description as defined in the annotation.
   *
   * @return string
   */
  public function getDescription();


  /**
   * Returns the processor instance UUID.
   *
   * @return string
   */
  public function getUuid();


  /**
   * Returns the processor instance human readable name.
   *
   * @return string
   */
  public function getName();


  /**
   * Returns the processor instance config summary.
   *
   * @return string
   */
  public function getSummary();


  /**
   * Returns the processor instance weight.
   *
   * @return integer
   *   The processor weight.
   */
  public function getWeight();


  /**
   * Runs the processor on the given data
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

  
  /**
   * Returns an array of pipes that are implicitly runs, when this plugin is
   * run.
   *
   * This method is used to detect and prevent cycles.
   *
   * @return
   *  An array with the pipe IDs or an empty array.
   */
  public function runsOnPipes();

  
  /**
   * Returns an array of field keys for the struct that this plugin reads,
   * either mandatory or optionally.
   *
   * Note that processors may work on any data, so this is only useful for
   * those acting on array/object data.
   *
   * @return array
   *  An array keyed by the field names. Values indicate whether the field is 
   *  mandatory (TRUE) or not (FALSE)
   */
  public function inputFields();


  /**
   * Returns an array of field keys for the struct that this plugin writes to.
   *
   * Note that processors may work on any data, so this is only useful for
   * those acting on array/object data.
   *
   * @return array
   *  An array keyed by the field names. 
   */
  public function outputFields();

  
  /**
   * Returns an array of tags that help to categorize the processor.
   * 
   * Typically these ar the same as from the plugin annotation but processors
   * may choose to override them to better represent their doings. Developers
   * should thus consider this method instead of directly using the tags from
   * the plugin definition. However, processors must only alter tags 
   * depending on their configuration as pipes will cache the tags.
   *
   * @return array
   *  Array of string tags each as key and value pair. 
   */
  public function getTags();


}

