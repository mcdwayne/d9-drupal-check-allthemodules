<?php

namespace Drupal\cloudconvert;

use Drupal\cloudconvert\Entity\CloudConvertTaskInterface;
use Drupal\cloudconvert\Entity\CloudConvertTaskTypeInterface;
use Drupal\file\FileInterface;

/**
 * Interface CloudConvertProcessorInterface.
 *
 * @package Drupal\cloudconvert
 */
interface CloudConvertProcessorInterface {

  /**
   * Create the CloudConvert process.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   * @param \Drupal\cloudconvert\Parameters $parameters
   *   Cloud Convert Parameters.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   */
  public function createProcess(CloudConvertTaskInterface $cloudConvertTask, Parameters $parameters);

  /**
   * Start the process for the given cloud convert task.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   */
  public function startProcess(CloudConvertTaskInterface $cloudConvertTask);

  /**
   * Finish the given cloud convert task.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   */
  public function finishProcess(CloudConvertTaskInterface $cloudConvertTask);

  /**
   * Get the CloudConvert Process.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   *
   * @return \Drupal\cloudconvert\Process
   *   Cloud Convert Process.
   */
  public function getProcess(CloudConvertTaskInterface $cloudConvertTask);

  /**
   * Create a cloud convert task for given file entity.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskTypeInterface $cloudConvertTaskType
   *   Cloud Convert Task Type Config Entity.
   * @param \Drupal\file\FileInterface $file
   *   File Entity.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   Cloud Convert Task Entity.
   */
  public function createTask(CloudConvertTaskTypeInterface $cloudConvertTaskType, FileInterface $file);

  /**
   * Download the file and create a file entity.
   *
   * @param \Drupal\cloudconvert\Process $process
   *   Cloud Convert Process.
   *
   * @return string
   *   Download destination.
   */
  public function downloadFile(Process $process);

  /**
   * Get the info data from the process.
   *
   * @param \Drupal\cloudconvert\Process $process
   *   Cloud Convert Process.
   *
   * @return object
   *   Information about the process.
   */
  public function gatherInfo(Process $process);

  /**
   * Get the CloudConvert API.
   *
   * @return \Drupal\cloudconvert\Api
   *   Cloud Convert API.
   */
  public function getCloudConvertApi();

  /**
   * Create a a Queue Item to start a process.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   * @param \Drupal\cloudconvert\Parameters $parameters
   *   Cloud Convert Parameters.
   */
  public function createStartQueueItem(CloudConvertTaskInterface $cloudConvertTask, Parameters $parameters);

  /**
   * Create a Queue Item to finish a process.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   */
  public function createFinishQueueItem(CloudConvertTaskInterface $cloudConvertTask);

  /**
   * Get the HOOK url for CloudConvert to call when the task is finished.
   *
   * @param \Drupal\cloudconvert\Entity\CloudConvertTaskInterface $cloudConvertTask
   *   Cloud Convert Task Entity.
   *
   * @return \Drupal\Core\Url
   *   Callback URL.
   */
  public function getCallbackUrl(CloudConvertTaskInterface $cloudConvertTask);

}
