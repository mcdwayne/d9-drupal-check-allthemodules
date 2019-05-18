<?php

namespace Drupal\Tests\dibs\Kernel;

use Drupal\dibs\Entity\DibsTransaction;
use Drupal\KernelTests\KernelTestBase;

abstract class DibsBaseKernelTest extends KernelTestBase {

  public static $modules = ['dibs'];

  protected function setUp() {
    parent::setUp();
    $this->installConfig('dibs');
  }

  /**
   * @param array $data
   * @return \Drupal\dibs\Entity\DibsTransaction
   */
  protected function getTransaction(array $data = []) {
    $data += [
      'status' => 'CREATED',
      'amount' => 12312313,
      'order_id' => '123123asdf',
      'currency' => 978,
      'email' => 'example@example.com',
      'billing_postal_code' => 'asdfasdf',
    ];

    return DibsTransaction::create($data);
  }

}
