<?php

namespace Drupal\maestro_template_builder\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An AJAX command for calling our own javascript functions/methods.
 *
 *
 * @ingroup maestro_template_builder
 */
class FireJavascriptCommand implements CommandInterface {

  /**
   * The Function to call in our maestro template.  Please see template-display.js and find the 
   * declaration for Drupal.AjaxCommands.prototype.addNewTask as an example of a javascript callback
   *
   * @var string
   */
  protected $function;

  /**
   * Keyed array of the values we want to pass back.
   * We cycle through these values and create the final return structure.
   * 
   * @var array $values
   */
  protected $values;
  

  /**
   * Constructs a our FireMaestroCommand
   *
   * @param string $function
   *   The javascript function you wish to call.
   * @param array $values
   *   Keyed array of values you wish to pass back to the javascript function being called.
   * 
   */
  public function __construct($function, $values) {
    $this->function = $function;
    $this->values = $values;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    $ret = array();
    $ret['command'] = $this->function;
    foreach($this->values as $key => $val) {
      $ret[$key] = $val;
    }
    return $ret;
  }

}
