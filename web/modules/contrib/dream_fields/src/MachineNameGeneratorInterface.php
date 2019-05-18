<?php

namespace Drupal\dream_fields;

/**
 * Interface MachineNameGeneratorInterface
 */
interface MachineNameGeneratorInterface {

  /**
   * Get a machine name from a given input.
   *
   * @param string $input
   *   The input string to provide a machine name for.
   * @return string
   *   A machine name.
   */
  public function getMachineName($input);

}
