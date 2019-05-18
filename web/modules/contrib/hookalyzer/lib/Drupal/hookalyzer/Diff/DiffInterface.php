<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\Diff\DiffInterface.
 */

namespace Drupal\hookalyzer\Diff;

/**
 * Describes hookalyzer Diff class behaviors.
 */
interface DiffInterface {

  const UNCHANGED = 0x00;
  const ADDED = 0x01;
  const REMOVED = 0x02;
  const VALUE_CHANGE = 0x04;
  const TYPE_CHANGE = 0x08;
  const OBJECT_INSTANCE_CHANGE = 0x10;
  const OBJECT_TYPE_CHANGE = 0x20;
  const OBJECT_FAMILY_CHANGE = 0x40;

  /**
   * Creates a new diff object with the provided values.
   *
   * @param mixed $val1
   *   The base value to compare.
   * @param mixed $val2
   *   The new value to compare.
   */
  public function __construct($val1, $val2);

  /**
   * Indicates the type of change, if any, via a bitflag.
   *
   * @return int
   */
  public function getChangeType();

  /**
   * Returns a human-interpretable string characterizing the diff, if any.
   *
   * @return mixed
   *   String if there was a diff, FALSE otherwise.
   */
  public function getVisualDiff();

  /**
   * Returns a string with a human-readable version of the data type.
   *
   * @return string
   */
  public function getType();
}