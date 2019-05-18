<?php

namespace Drupal\contextual_cleaner\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class ContextualClearCommand implements CommandInterface {

  public function __construct() {}

  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {

    return array(
      'command' => 'clearContextual',
    );
  }
}
