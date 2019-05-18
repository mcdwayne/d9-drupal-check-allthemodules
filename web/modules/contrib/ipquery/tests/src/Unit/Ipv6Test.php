<?php

namespace Drupal\Tests\ipquery\Unit;

use Drupal\ipquery\BaseService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the IPv6 number conversions.
 *
 * @group ipquery
 *
 * @see https://www.ipaddressguide.com/ipv6-to-decimal
 * @see http://www.maxi-pedia.com/dec+to+hex+converter
 */
class ipv6Test extends UnitTestCase {

  /**
   * The base service.
   *
   * @var \Drupal\ipquery\BaseService
   */
  protected $base;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->base = new BaseService;
  }

  public function testNumberToLong00000000000000010000000000000000() {
    if ($this->base->isIpv6Supported()) {
      list($left, $right) = $this->base->numberToLong('18446744073709551616');
      $this->assertEquals(1, $left);
      $this->assertEquals(0, $right);
    }
  }

  public function testNumberToLongFEDCBA98765432100123456789ABCDEF() {
    if ($this->base->isIpv6Supported()) {
      list($left, $right) = $this->base->numberToLong('338770000845734292516042252062085074415');
      $this->assertEquals(0xFEDCBA9876543210, $left);
      $this->assertEquals(0x0123456789ABCDEF, $right);
    }
  }

  public function testIpToLongFEDCBA98765432100123456789ABCDEF() {
    if ($this->base->isIpv6Supported()) {
      list($left, $right) = $this->base->ipToLong('FEDC:BA98:7654:3210:0123:4567:89AB:CDEF');
      $this->assertEquals(0xFEDCBA9876543210, $left);
      $this->assertEquals(0x0123456789ABCDEF, $right);
    }
  }

}
