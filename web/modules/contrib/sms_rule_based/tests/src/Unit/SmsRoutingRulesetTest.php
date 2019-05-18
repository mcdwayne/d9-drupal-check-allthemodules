<?php

namespace Drupal\Tests\sms_rule_based\Unit;

use Drupal\Component\Utility\Random;
use Drupal\Component\Uuid\Php;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms_rule_based\Entity\SmsRoutingRuleset;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Area;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Country;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Number;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Recipients;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Sender;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\Sendtime;
use Drupal\sms_rule_based\Plugin\SmsRoutingRule\User;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;
use Drupal\sms_rule_based\RuleBasedSmsRouter;
use Drupal\user\Entity\User as UserEntity;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\sms_rule_based\Entity\SmsRoutingRuleset
 * @group SMS Rule based
 */
class SmsRoutingRulesetTest extends KernelTestBase {

  protected $entityStorage;

  public function setUp() {
    parent::setUp();

    // Mock the entity manager.
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_repository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $entity_type_repository->getEntityTypeFromClass(SmsRoutingRuleset::class)
      ->willReturn('test_entity_type');
    $entity_type_repository->getEntityTypeFromClass(UserEntity::class)
      ->willReturn('user');
    $entity_type_manager->getStorage('test_entity_type')
      ->willReturn($this->entityStorage);
    $entity_type_manager->getStorage('user')
      ->willReturn($this->entityStorage);

    // Set up the container.
    $container = new ContainerBuilder();
    $container->set('uuid', new Php());
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('entity_type.repository', $entity_type_repository->reveal());

    $entity_manager = new EntityManager();
    $entity_manager->setContainer($container);
    $container->set('entity.manager', $entity_manager);
    \Drupal::setContainer($container);
  }

  /**
   * @dataProvider providerRuleBasedRoutingRulesets
   */
  public function testRuleBasedRoutingRulesets(array $rulesets, array $numbers, array $context, array $routed_array, array $default_routed) {
    // Set up return value for entity storage stub.
    $uuid_service = new Php();
    $ruleset_objects = [];
    foreach ($rulesets as $name => $ruleset) {
      $ruleset += ['uuid' => $uuid_service->generate()];
      $ruleset_objects[$name] = new TestSmsRoutingRuleset($ruleset);
    }
    $sender = isset($context['sender']) ? $context['sender'] : '';
    $uid = isset($context['uid']) ? $context['uid'] : 0;
    $sms = new SmsMessage($sender, $numbers, 'test message', [], $uid);
    $router = new RuleBasedSmsRouter();
    $routing = $router->routeSmsRecipients($sms, $ruleset_objects);
    foreach ($routed_array as $gateway => $numbers) {
      $this->assertEquals($routing['routes'][$gateway], $numbers);
    }
    if ($default_routed) {
      $this->assertNotContains($default_routed, $routing['routes']['__default__']);
    }
  }

  /**
   * @todo More test cases needed.
   */
  public function providerRuleBasedRoutingRulesets() {
    $block_user_expected = [
      '2348191234500', '2342342342342', '9879879897987',
      '2345419243834', '2349823472628', '345921092347',
      '2342092378432', '2346587457634', '345583672763',
    ];
    return [
      [
        [$this->rulesets['cdma']],
        self::$all_numbers,
        [],
        ['42_cdma' => ['2348191234500', '9879879897987']],
        array_diff(self::$all_numbers, ['2348191234500', '9879879897987']),
      ],
      [
        [$this->rulesets['debug']],
        self::$all_numbers,
        ['sender' => 'debug'],
        ['log' => self::$all_numbers],
        [],
      ],
      [
        [$this->rulesets['block_user']],
        self::$all_numbers,
        ['uid' => 4],
        ['log' => $block_user_expected],
        array_diff(self::$all_numbers, $block_user_expected),
      ],
      [
        [$this->rulesets['direct']],
        self::$all_numbers,
        [],
        [],
        self::$all_numbers,
      ],
    ];
  }

  protected $rulesets = [
    'cdma' => [
      'name' => 'cdma',
      'enabled' => 1,
      'description' => '',
      '_ALL_TRUE_' => TRUE,
      'rules' => [
        'number' => [
          'operator' => 'IN',
          'negated' => FALSE,
          'operand' => '234819%,987987%',
        ],
      ],
      'gateway' => '42_cdma',
      'weight' => '-4'
    ],
    'debug' => [
      'name' => 'debug',
      'enabled' => 1,
      'description' => 'Used for quick debugging purposes. Send to log.',
      '_ALL_TRUE_' => FALSE,
      'rules' => [
        'sender' => [
          'operator' => 'EQ',
          'negated' => FALSE,
          'operand' => 'debug',
        ],
      ],
      'gateway' => 'log',
      'weight' => '-8',
    ],
    'direct' => [
      'name' => 'direct',
      'enabled' => 0,
      'description' => 'Direct numbers to foobar gateway',
      '_ALL_TRUE_' => '',
      'rules' => [
        'number' => [
          'operator' => 'IN',
          'negated' => '',
          'operand' => '347508%,481202%,854208%,1234%',
        ],
      ],
      'gateway' => 'i_direct',
      'weight' => '-1',
    ],
    'block_user' => [
      'name' => 'block_user',
      'enabled' => 1,
      'description' => 'Block a user (disabled)',
      '_ALL_TRUE_' => TRUE,
      'rules' => [
        'user' => [
          'operator' => 'EQ',
          'negated' => '',
          'operand' => '4',
        ],
        'number' => [
          'operator' => 'IN',
          'negated' => '',
          'operand' => '234%,345%,987%',
        ],
      ],
      'gateway' => 'log',
      'weight' => '-6',
    ],
    'spammers' => [
      'name' => 'spammers',
      'enabled' => 1,
      'description' => 'Route to send spammers to debug gateway',
      '_ALL_TRUE_' => '1',
      'rules' => [
        'user' => [
          'operator' => 'IN',
          'negated' => '',
          'operand' => 'Godslovee, gur kimhi',
        ],
        'count' => [
          'operator' => 'GT',
          'negated' => '',
          'operand' => '20',
        ],
        'country' => [
          'operator' => 'EQ',
          'negated' => '1',
          'operand' => '234',
        ],
      ],
      'gateway' => 'debug',
      'weight' => '-9',
    ],
    'international' => [
      'name' => 'international',
      'enabled' => 1,
      'description' => 'International SMS through llamas',
      '_ALL_TRUE_' => '',
      'rules' => [
        'country' => [
          'operator' => 'EQ',
          'negated' => '1',
          'operand' => '234',
        ],
        'user' => [
          'operator' => 'EQ',
          'negated' => '',
          'operand' => 'boku',
        ],
      ],
      'gateway' => 'llamas',
      'weight' => '-5',
    ],
  ];

  protected static $all_numbers = [
    '2348191234500', '2342342342342', '343092384272', '9879879897987',
    '2345419243834', '2356098341932', '343821748269', '12392814981',
    '2349823472628', '2308237312356', '345921092347', '89234723439',
    '2342092378432', '2346587457634', '345583672763', '927198324412',
  ];

}

class TestSmsRoutingRuleset extends SmsRoutingRuleset {

  public function __construct(array $values) {
    parent::__construct($values, 'sms_routing_ruleset');
    $this->random = new Random();
    $this->rules = $this->objectifyRules($values['rules']);
  }

  public function getRules() {
    return $this->rules;
  }

  protected function objectifyRules($rules) {
    $rule_objects = [];
    foreach ($rules as $type => $rule) {
      $rule_objects[$type] = $this->createPluginInstance($type, $rule);
    }
    return $rule_objects;
  }
  
  protected function createPluginInstance($plugin_id, array $configuration) {
    $configuration += $this->defaultConfiguration;
    $configuration['name'] = $this->random->name();
    $plugin_definition = [
      'label' => $this->random->word(3),
      'description' => $this->random->sentences(3),
    ];
    switch ($plugin_id) {
      case 'area':
        return new Area($configuration, $plugin_id, $plugin_definition);
      case 'country':
        return new Country($configuration, $plugin_id, $plugin_definition);
      case 'number':
        return new Number($configuration, $plugin_id, $plugin_definition);
      case 'count':
        return new Recipients($configuration, $plugin_id, $plugin_definition);
      case 'sender':
        return new Sender($configuration, $plugin_id, $plugin_definition);
      case 'sendtime':
        return new Sendtime($configuration, $plugin_id, $plugin_definition);
      case 'user':
        return new User($configuration, $plugin_id, $plugin_definition);
    }
    return null;
  }
  
  protected $defaultConfiguration = [
    'enabled' => TRUE,
    'operator' => SmsRoutingRulePluginBase::EQ,
    'operand' => '',
    'negated' => FALSE,
  ];
  
  protected $random;
  
}
