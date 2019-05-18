<?php
namespace Drupal\monster_menus;

/**
 * @class Exception class used to throw error if import/export fails. Includes
 * translation with t().
 */
class MMImportExportException extends \Exception {

  private $vars;

  public function __construct($message, $vars = array()) {
    $this->vars = $vars;
    parent::__construct($message);
  }

  public function __toString() {
    return t(parent::__toString(), $this->vars);
  }

  public function getTheMessage() {
    return t(parent::getMessage(), $this->vars);
  }

}
