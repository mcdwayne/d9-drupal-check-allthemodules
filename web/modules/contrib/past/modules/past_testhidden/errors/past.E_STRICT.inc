<?php
/**
 * @file
 * Generates an E_STRICT level error.
 */

/**
 * Example class to trigger an E_STRICT error.
 */
class Strict {

  /**
   * Method that is not defined as static.
   */
  public function test() {
    return 'Test';
  }

}

// This will trigger a "no-strict method should not be called statically" error.
Strict::test();
