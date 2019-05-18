<?php

namespace Drupal\link_partners\vendor\Sape;

/**
 * Глобальные флаги
 */
class SAPE_globals {


  protected function _get_toggle_flag($name, $toggle = FALSE) {

    static $flags = [];

    if (!isset($flags[$name])) {
      $flags[$name] = FALSE;
    }

    if ($toggle) {
      $flags[$name] = TRUE;
    }

    return $flags[$name];
  }

  public function block_css_shown($toggle = FALSE) {
    return $this->_get_toggle_flag('block_css_shown', $toggle);
  }

  public function block_ins_beforeall_shown($toggle = FALSE) {
    return $this->_get_toggle_flag('block_ins_beforeall_shown', $toggle);
  }

  public function page_obligatory_output_shown($toggle = FALSE) {
    return $this->_get_toggle_flag('page_obligatory_output_shown', $toggle);
  }
}
