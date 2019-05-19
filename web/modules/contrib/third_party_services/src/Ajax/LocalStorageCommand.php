<?php

namespace Drupal\third_party_services\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Manipulate by "window.localStorage" within the frontend.
 */
class LocalStorageCommand implements CommandInterface {

  /**
   * One of "window.localStorage" methods.
   *
   * @var string
   */
  private $method = '';
  /**
   * Any set of argument which might be required by a method.
   *
   * @var string[]
   */
  private $arguments = [];

  /**
   * LocalStorageCommand constructor.
   *
   * @param string $method
   *   One of "window.localStorage" methods. Pretty useful are:
   *   - "setItem"
   *   - "removeItem"
   *   - "clear".
   * @codingStandardsIgnoreStart
   * @param string[] ...$arguments
   * @codingStandardsIgnoreEnd
   *   Any set of argument which might be required by a method.
   *
   * @code
   * // Store/update the item "key" in local storage.
   * new LocalStorageCommand('setItem', 'key', 'value');
   * // Remove the "key" item.
   * new LocalStorageCommand('setItem', 'key');
   * // Clear whole storage.
   * new LocalStorageCommand('clear');
   * @endcode
   */
  public function __construct(string $method, string ...$arguments) {
    $this->method = $method;
    $this->arguments = $arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    return [
      'command' => 'localStorage',
      'method' => $this->method,
      'args' => $this->arguments,
    ];
  }

}
