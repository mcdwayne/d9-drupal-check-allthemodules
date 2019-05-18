<?php

namespace Drupal\cloudconvert\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\file\FileInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining CloudConvert Task entities.
 *
 * @ingroup cloudconvert
 */
interface CloudConvertTaskInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the CloudConvert Task creation timestamp.
   *
   * @return int
   *   Creation timestamp of the CloudConvert Task.
   */
  public function getCreatedTime();

  /**
   * Sets the CloudConvert Task creation timestamp.
   *
   * @param int $timestamp
   *   The CloudConvert Task creation timestamp.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get the step of the process the task is currently in.
   *
   * @return string
   *   The name of the cloudconvert step.
   */
  public function getStep();

  /**
   * Set the step of the process the task is currently in.
   *
   * @param string $step
   *   The name of the cloudconvert step.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setStep($step);

  /**
   * Get the CloudConvert process id.
   *
   * @return string
   *   Cloud Convert Process ID.
   */
  public function getProcessId();

  /**
   * Set the CloudConvert process id.
   *
   * @param string $processId
   *   Cloud Convert Process ID
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setProcessId($processId);

  /**
   * Get the last known process information of the task.
   *
   * @return array
   *   List with Process Information.
   */
  public function getProcessInfo();

  /**
   * Set the last known process information of the task.
   *
   * @param array $processInfo
   *   List with Process Information.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setProcessInfo(array $processInfo);

  /**
   * Get the last known parameters used for the task.
   *
   * @return array
   *   List with Process Parameters.
   */
  public function getProcessParameters();

  /**
   * Set the last known parameters used for the task.
   *
   * @param array $parameters
   *   List with Process Parameters.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setProcessParameters(array $parameters);

  /**
   * Update some parameters.
   *
   * @param array $processParameters
   *   List with Process Parameters.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   Cloud Convert Task.
   */
  public function updateProcessParameters(array $processParameters);

  /**
   * Get the original file ID.
   *
   * @return int
   *   Original File Entity ID.
   */
  public function getOriginalFileId();

  /**
   * Set the original file ID.
   *
   * @param int $originalFileId
   *   Original File Entity ID.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setOriginalFileId($originalFileId);

  /**
   * Get the Original File.
   *
   * @return \Drupal\file\FileInterface
   *   Original File Entity.
   */
  public function getOriginalFile();

  /**
   * Set the Original File.
   *
   * @param \Drupal\file\FileInterface $originalFile
   *   Original File Entity.
   *
   * @return \Drupal\cloudconvert\Entity\CloudConvertTaskInterface
   *   The called CloudConvert Task entity.
   */
  public function setOriginalFile(FileInterface $originalFile);

}
