<?php

namespace Drupal\formassembly\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining FormAssembly Form entities.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 * @package Drupal\formassembly
 */
interface FormAssemblyEntityInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the FormAssembly Form name.
   *
   * @return string
   *   Name of the FormAssembly Form.
   */
  public function getName();

  /**
   * Sets the FormAssembly Form name.
   *
   * @param string $name
   *   The FormAssembly Form name.
   *
   * @return \Drupal\formassembly\Entity\FormAssemblyEntityInterface
   *   The called FormAssembly Form entity.
   */
  public function setName($name);

  /**
   * Gets the FormAssembly Form creation timestamp.
   *
   * @return int
   *   Creation timestamp of the FormAssembly Form.
   */
  public function getCreatedTime();

  /**
   * Sets the FormAssembly Form creation timestamp.
   *
   * @param int $timestamp
   *   The FormAssembly Form creation timestamp.
   *
   * @return \Drupal\formassembly\Entity\FormAssemblyEntityInterface
   *   The called FormAssembly Form entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the FormAssembly Form modification timestamp.
   *
   * @return int
   *   Modification timestamp of the FormAssembly Form.
   */
  public function getModifiedTime();

  /**
   * Sets the FormAssembly Form modification timestamp.
   *
   * @param int $timestamp
   *   The FormAssembly Form modification timestamp.
   *
   * @return \Drupal\formassembly\Entity\FormAssemblyEntityInterface
   *   The called FormAssembly Form entity.
   */
  public function setModifiedTime($timestamp);

  /**
   * Get the published status of the form.
   *
   * @return bool
   *   The value of the status field.
   */
  public function getStatus();

  /**
   * Sets fa_form status to 1-published.
   *
   * @return \Drupal\formassembly\Entity\FormAssemblyEntity
   *   The called FormAssembly Form entity.
   */
  public function enable();

  /**
   * Sets fa_form status to 0-unpublished.
   *
   * @return \Drupal\formassembly\Entity\FormAssemblyEntity
   *   The called FormAssembly Form entity.
   */
  public function disable();

}
