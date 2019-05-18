<?php

/**
 * @file
 * Log printer service for console logger module.
 */

namespace Drupal\console_logger;

use JakubOnderka\PhpConsoleColor\ConsoleColor;

class LogPrinter {

  /**
   * The console color service.
   *
   * @var \JakubOnderka\PhpConsoleColor\ConsoleColor
   */
  protected $console_color;

  public function printToConsole($style, $message) {
    if (php_sapi_name() == 'cli-server') {
      file_put_contents("php://stdout", sprintf("\n%s\n", $this->getConsoleColor()->apply($style, $message)));
    }
  }

  /**
   * @return ConsoleColor
   */
  public function getConsoleColor() {
    if (!$this->console_color) {
      $this->console_color = new ConsoleColor();
    }
    return $this->console_color;
  }

  /**
   * @param ConsoleColor $console_color
   */
  public function setConsoleColor($console_color) {
    $this->console_color = $console_color;
  }
}
