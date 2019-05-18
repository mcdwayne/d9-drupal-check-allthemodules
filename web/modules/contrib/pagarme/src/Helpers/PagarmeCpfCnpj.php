<?php

namespace Drupal\pagarme\Helpers;

class PagarmeCpfCnpj {

  private $cpf_cnpj;

  function __construct ($cpf_cnpj = null) {
    $this->cpf_cnpj = (string) $cpf_cnpj;
  }

  static public function valid($cpf_cnpj) {
    $obj = new \Drupal\pagarme\Helpers\PagarmeCpfCnpj($cpf_cnpj);
    return $obj->verify_cpf_cnpj();
  }

  public function verify_cpf_cnpj() {
    if (strlen($this->cpf_cnpj) === 11) {
      return $this->valid_cpf();
    } 
    elseif (strlen( $this->cpf_cnpj ) === 14) {
      return $this->valid_cnpj();
    }
    return false;
  }

  protected function valid_cpf() {
    $digits = substr($this->cpf_cnpj, 0, 9);
    $new_cpf = $this->calc_position_digits($digits);
    $new_cpf = $this->calc_position_digits($new_cpf, 11);
    return ($new_cpf === $this->cpf_cnpj) ? true : false;
  }

  protected function valid_cnpj() {
    $cnpj_original = $this->cpf_cnpj;
    $first_numbers_cnpj = substr($this->cpf_cnpj, 0, 12);
    $first_calculation = $this->calc_position_digits($first_numbers_cnpj, 5);
    $second_calculation = $this->calc_position_digits($first_calculation, 6);
    $cnpj = $second_calculation;
    return ($cnpj === $cnpj_original) ? true : false;
  }

  protected function calc_position_digits($digits, $positions = 10, $sum_digits = 0) {
    for ($i = 0; $i < strlen( $digits ); $i++) {
      $sum_digits = $sum_digits + ( $digits[$i] * $positions );
      $positions--;
      if ( $positions < 2 ) {
        $positions = 9;
      }
    }

    $sum_digits = $sum_digits % 11;
    $sum_digits = ($sum_digits < 2) ? 0 : 11 - $sum_digits;
    return $digits . $sum_digits;
  }
}