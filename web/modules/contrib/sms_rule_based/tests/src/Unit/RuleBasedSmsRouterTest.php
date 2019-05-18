<?php

namespace Drupal\Tests\sms_rule_based\Unit;

use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms_rule_based\Entity\SmsRoutingRuleset;
use Drupal\sms_rule_based\RuleBasedSmsRouter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sms_rule_based\RuleBasedSmsRouter
 * @group SMS Rule based
 */
class RuleBasedSmsRouterTest extends UnitTestCase {

  /**
   * The SMS router for test.
   *
   * @var \Drupal\sms_rule_based\RuleBasedSmsRouter
   */
  protected $smsRouter;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $smsMessage;

  public function setUp() {
    parent::setUp();
    $this->smsRouter = new RuleBasedSmsRouter();
  }

  /**
   * Carries out tests using the RuleBasedSmsRouter
   *
   * @param \Drupal\sms_rule_based\Entity\SmsRoutingRuleset[] $rulesets
   *   The rulesets.
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The SMS message.
   * @param array $expected_routing
   *   The numbers expected to be routed according to this ruleset.
   *
   * @covers ::routeSmsRecipients
   * @dataProvider providerRuleBasedRoutingRulesets
   */
  public function testSmsRoutingRulesets(array $rulesets, SmsMessageInterface $sms, array $expected_routing) {
    $actual_routing = $this->smsRouter->routeSmsRecipients($sms, $rulesets);
    $this->assertEquals($expected_routing, $actual_routing['routes']);
  }

  /**
   * Data provider for testSmsRoutingRulesets.
   *
   * @return array
   */
  public function providerRuleBasedRoutingRulesets() {
    $recipients = [
      '2348191234500', '2348101234500', '2348171234500', '2348031234500',
    ];
    $message = $this->getRandomGenerator()->sentences(120);
    $sms_prophecy = $this->prophesize(SmsMessageInterface::class);
    $sms_prophecy->getRecipients()->willReturn($recipients);
    $sms_prophecy->getMessage()->willReturn($message);
    $sms_prophecy->getOptions()->willReturn([]);
    $sms_prophecy->getSenderNumber()->willReturn('test_sender');
    $sms_prophecy->getUuid()->willReturn($this->randomMachineName(32));
    $sms_prophecy->getUid()->willReturn(1);
    $sms = $sms_prophecy->reveal();
    return [
      [
        [$ruleset = $this->buildRandomRuleset(['number_group1'])],
        $sms,
        [$ruleset->get('gateway') => ['2348191234500'], '__default__' => ['2348101234500', '2348171234500', '2348031234500']],
      ],
      [
        [$ruleset = $this->buildRandomRuleset(['number_group4'])],
        $sms,
        [$ruleset->get('gateway') => ['2348171234500'], '__default__' => ['2348191234500', '2348101234500', '2348031234500']],
      ],
      [
        [$ruleset = $this->buildRandomRuleset(['number_group2'])],
        $sms,
        [$ruleset->get('gateway') => ['2348101234500', '2348031234500'], '__default__' => ['2348191234500', '2348171234500']],
      ],
      [
        [$ruleset1 = $this->buildRandomRuleset(['number_group1']), $ruleset2 = $this->buildRandomRuleset(['number_group2'])],
        $sms,
        [$ruleset1->get('gateway') => ['2348191234500'], $ruleset2->get('gateway') => ['2348101234500', '2348031234500'], '__default__' => ['2348171234500']],
      ],
      [
        [$ruleset1 = $this->buildRandomRuleset(['sender_check']), $ruleset2 = $this->buildRandomRuleset(['number_group2'])],
        $sms,
        [$ruleset1->get('gateway') => $recipients],
      ],
      [
        [$ruleset = $this->buildRandomRuleset(['country_ng'])],
        $sms,
        [$ruleset->get('gateway') => $recipients],
      ],
    ];
  }

  /**
   * Builds a random ruleset based on the supplied rule names.
   *
   * @param array $rule_names
   *   A list of rule names from the static::$rules array.
   *
   * @return object
   *   A prophesy ruleset stub object.
   */
  protected function buildRandomRuleset(array $rule_names) {
    $ruleset = $this->prophesize(SmsRoutingRuleset::class);
    $ruleset->get('name')->willReturn($this->randomMachineName());
    $ruleset->get('enabled')->willReturn(TRUE);
    $ruleset->get('gateway')->willReturn($this->randomMachineName());
    $ruleset->get('_ALL_TRUE_')->willReturn((rand(0, 1) > 0.5));
    $rules = [];
    foreach ($rule_names as $rule_name) {
      $rules[$this->randomMachineName()] = $this->getSmsRoutingRule(static::$rules[$rule_name]);
    }
    $ruleset->getRules()->willReturn($rules);
    return $ruleset->reveal();
  }

  /**
   * Returns an SMS routing rule plugin object.
   *
   * @param array $configuration
   *   The configuration to be used to create the plugin.
   *
   * @return \Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface
   *   The plugin instance.
   */
  protected function getSmsRoutingRule(array $configuration) {
    $class = static::$pluginMap[$configuration['type']];
    return new $class($configuration, $this->randomMachineName(), []);
  }

  /**
   * A map of plugins types to the corresponding fully-qualified class name.
   *
   * @var array
   */
  protected static $pluginMap = [
    'area' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Area',
    'country' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Country',
    'number' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Number',
    'recipients' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Recipients',
    'sender' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Sender',
    'sendtime' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\Sendtime',
    'user' => '\Drupal\sms_rule_based\Plugin\SmsRoutingRule\User',
  ];

  /**
   * Some pre-canned rule definitions.
   *
   * @var array
   */
  protected static $rules = [
    'number_group1' => [
      'operator' => 'IN',
      'negated' => FALSE,
      'operand' => '234819%,234704%,234702%,234709%,234707%',
      'enabled' => 1,
      'type' => 'number',
    ],
    'number_group2' => [
      'operator' => 'IN',
      'negated' => '',
      'operand' => '234703%,234706%,234803%,234806%,234810%,234813%,234816%',
      'type' => 'number',
    ],
    'number_group3' => [
      'operator' => 'IN',
      'negated' => '',
      'operand' => '234708%,234802%,234808%,234812%',
      'enabled' => 1,
      'type' => 'number',
    ],
    'number_group4' => [
      'operator' => 'IN',
      'negated' => '',
      'operand' => '234809%,234817%,234818%',
      'type' => 'number',
    ],
    'number_group5' => [
      'operator' => 'IN',
      'negated' => '',
      'operand' => '234705%,234805%,234807%,234815%,234811%',
      'type' => 'number',
    ],
    'sender_check' => [
      'operator' => 'EQ',
      'negated' => '',
      'operand' => 'test_sender',
      'enabled' => 1,
      'type' => 'sender',
    ],
    'user_check' => [
      'operator' => 'EQ',
      'negated' => '',
      'operand' => 'test_user',
      'enabled' => 1,
      'type' => 'user',
    ],
    'recipients_high' => [
      'operator' => 'GT',
      'negated' => '',
      'operand' => '20',
      'type' => 'recipients',
    ],
    'country_ng' => [
      'operator' => 'EQ',
      'negated' => '0',
      'operand' => '234',
      'type' => 'country',
    ],
    'country_not_usa' => [
      'operator' => 'EQ',
      'negated' => '1',
      'operand' => '1',
      'type' => 'country',
    ],
    'area_check' => [
      'operator' => 'EQ',
      'negated' => '1',
      'operand' => '805',
      'type' => 'area',
    ],
  ];

}
