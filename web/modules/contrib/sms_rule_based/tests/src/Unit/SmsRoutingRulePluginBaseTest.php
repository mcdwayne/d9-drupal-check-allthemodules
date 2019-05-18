<?php

namespace Drupal\Tests\sms_rule_based\Unit;

use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase
 * @group SMS Rule based
 */
class SmsRoutingRulePluginBaseTest extends UnitTestCase {

  /**
   * The SMS router for test.
   *
   * @var \Drupal\Tests\sms_rule_based\Unit\TestRoutingRule
   */
  protected $smsRoutingRule;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->smsRoutingRule = new TestRoutingRule([], $this->randomMachineName(), []);
  }

  /**
   * @covers ::satisfiesExpression
   * @dataProvider providerSatisfiesExpression
   */
  public function testSatisfiesExpression($param, $operator, $operand, $expected = TRUE) {
    $context = [
      'param' => $param,
      'operator' => $operator,
      'operand' => $operand,
      'negated' => FALSE,
    ];
    $this->assertSame($expected, $this->smsRoutingRule->match([], $context));
    $context['negated'] = TRUE;
    $this->assertNotSame($expected, $this->smsRoutingRule->match([], $context));
  }

  /**
   * Data provider for testSatisfiesExpression.
   *
   * @return array
   */
  public function providerSatisfiesExpression() {
    return [
    //[$param, $operator, $operand, $negated, $expected]
      [3, SmsRoutingRulePluginBase::EQ, 2, FALSE],
      [3, SmsRoutingRulePluginBase::EQ, 3, TRUE],
      [3, SmsRoutingRulePluginBase::GT, 2, TRUE],
      [3, SmsRoutingRulePluginBase::GT, 3, FALSE],
      [3, SmsRoutingRulePluginBase::GE, 2, TRUE],
      [3, SmsRoutingRulePluginBase::GE, 3, TRUE],
      ['ABRA', SmsRoutingRulePluginBase::LT, 'abra', TRUE],
      ['ABRA', SmsRoutingRulePluginBase::LT, 'ABRA', FALSE],
      ['abra', SmsRoutingRulePluginBase::LT, 'ABRA', FALSE],
      ['ABRA', SmsRoutingRulePluginBase::LE, 'abra', TRUE],
      ['ABRA', SmsRoutingRulePluginBase::LE, 'ABRA', TRUE],
      ['abra', SmsRoutingRulePluginBase::LE, 'ABRA', FALSE],
      ['abra', SmsRoutingRulePluginBase::IN, 'abra, cada, bra', TRUE],
      ['abra', SmsRoutingRulePluginBase::IN, 'ABRA, CADA, BRA', TRUE],
      ['abra', SmsRoutingRulePluginBase::IN, 'ABRAM, CADA, %BRA', TRUE],
      ['abraco', SmsRoutingRulePluginBase::IN, 'ABRA%, CADA, BRA', TRUE],
      ['abraco', SmsRoutingRulePluginBase::IN, 'ABRA, CADA, %BRA', FALSE],
      ['abraco', SmsRoutingRulePluginBase::IN, 'ABRA, CADA, BRA', FALSE],
      ['cadam', SmsRoutingRulePluginBase::IN, 'ABRA%, CADA, BRA', FALSE],
      ['cadam', SmsRoutingRulePluginBase::IN, 'ABRA, CADA%, BRA', TRUE],
      ['cada,', SmsRoutingRulePluginBase::IN, 'ABRA, CADA, %BRA', FALSE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, 'ABRA', FALSE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, 'ABRA%', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, '%ABRA%', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, '%ABR%CADA%', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, '%ABR%A', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::LK, '%ABR%A, HELLO', FALSE],
      ['abracadabra', SmsRoutingRulePluginBase::RX, '%ABRA%', FALSE],
      ['abracadabra', SmsRoutingRulePluginBase::RX, 'ABR.*CADA.*', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::RX, '.?ABR.+A', TRUE],
      ['abracadabra', SmsRoutingRulePluginBase::RX, 'ABR.*A, HELLO', FALSE],
      // Other random tests.
      ['hello', SmsRoutingRulePluginBase::EQ, 'hello, world', FALSE],
    ];
  }

}

class TestRoutingRule extends SmsRoutingRulePluginBase {

  /**
   * {@inheritdoc}
   */
  public function match(array $numbers, array $context) {
    $this->configuration = $context + $this->configuration;
    return $this->satisfiesExpression($context['param']);
  }

}
