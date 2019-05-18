<?php

namespace Drupal\fancy_login\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines the load form ajax command.
 */
class FancyLoginLoadFormCommand implements CommandInterface {

  /**
   * The loaded form.
   *
   * @var array
   */
  protected $form;

  /**
   * Constructs a FancyLoginLoadFormCommand object.
   */
  public function __construct(array $form) {
    $this->form = $form;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'fancyLoginLoadFormCommand',
      'form' => render($this->form),
    ];
  }

}
