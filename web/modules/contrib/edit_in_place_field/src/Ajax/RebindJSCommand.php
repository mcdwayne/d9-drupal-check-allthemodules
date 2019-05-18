<?php

namespace Drupal\edit_in_place_field\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class RebindJSCommand implements CommandInterface {

  /**
   * JavaScript function name to be call.
   *
   * @var string
   */
  protected $jsFunctionName;
  /**
   * jQuery selector to be used.
   *
   * @var string
   */
  protected $containerJquerySelector;

  public function __construct($js_function_name, $container_jquery_selector) {
    $this->jsFunctionName = $js_function_name;
    $this->containerJquerySelector = $container_jquery_selector;
  }
  // Implements Drupal\Core\Ajax\CommandInterface:render().
  public function render() {
    return array(
      'command' => $this->jsFunctionName,
      'containerJquerySelector' => $this->containerJquerySelector,
    );
  }
}