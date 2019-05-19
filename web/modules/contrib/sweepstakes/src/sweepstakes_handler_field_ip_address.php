<?php
namespace Drupal\sweepstakes;

class sweepstakes_handler_field_ip_address extends views_handler_field_numeric {

  public function render($values) {
    $value = $this->get_value($values);
    return $value > 0 ? long2ip($this->get_value($values)) : '-';
  }
}
