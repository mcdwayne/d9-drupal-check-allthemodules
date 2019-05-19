<?php

namespace Drupal\Tests\sir_trevor\Unit\TestDoubles;

class FieldItemListMock extends FieldItemListDummy {

  private $string;

  public function setString($string) {
    $this->string = $string;
  }

  public function getString() {
    return $this->string;
  }
}
