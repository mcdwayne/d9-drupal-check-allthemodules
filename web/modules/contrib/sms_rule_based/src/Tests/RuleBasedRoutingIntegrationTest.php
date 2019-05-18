<?php

namespace Drupal\sms_rule_based\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\sms\Message\SmsMessage;
use Drupal\sms\Tests\SmsFrameworkTestTrait;
use Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginBase;

/**
 * Integration tests for rule-based routing of SMS.
 *
 * @group SMS Rule Based
 */
class RuleBasedRoutingIntegrationTest extends WebTestBase {

  use SmsFrameworkTestTrait;

  public static $modules = ['sms', 'sms_test_gateway', 'sms_rule_based'];

  /**
   * Storage handler for SMS routing rulesets.
   * 
   * @var \Drupal\Core\Entity\EntityStorageInterface $ruleset_storage
   */
  protected $rulesetStorage;

  /**
   * Rule-based routing SMS provider.
   * 
   * @var \Drupal\sms\Provider\SmsProviderInterface $sms_provider
   */
  protected $smsProvider;
  
  public function setUp() {
    parent::setUp();
    $this->rulesetStorage = $this->container
      ->get('entity_type.manager')
      ->getStorage('sms_routing_ruleset');

    $this->smsProvider = $this->container->get('sms.provider');
  }

  public function testRuleBasedRoutingRulesForm() {
    $user = $this->drupalCreateUser(['administer rule-based routing', 'administer smsframework']);
    $this->drupalLogin($user);
    $url = new Url('entity.sms_routing_ruleset.list');
    $this->drupalGet($url);

    // Enable rule-based routing.
    $this->drupalPostForm(NULL, ['enable' => TRUE], 'Save configuration');

    // Uncomment this when local task and local action block placement is
    // available in tests.
//    $this->clickLinkPartialName('Add ruleset');
    $this->drupalGet(new Url('entity.sms_routing_ruleset.add_form'));

    // Confirm there is validation on posting incomplete content.
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertText('Label field is required.');
    $this->assertText('Name field is required.');

    $edit = [
      'label' => trim($this->randomString()),
      'name' => strtolower($this->randomMachineName()),
      'enabled' => TRUE,
      'weight' => -2,
      'gateway' => 'log',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('No rule has been created in this ruleset.');

    // Create a new rule and add some info.
    $this->drupalPostForm(NULL, [], 'Add rule');
    $this->assertFieldByXPath('//input[starts-with(@name, "area")]');
    $this->assertText('Area code');

    // Add 3 more rule fields
    $this->drupalPostForm(NULL, ['rules[new][type]' => 'country'], 'Add rule');
    $this->assertFieldByXPath('//input[starts-with(@name, "country")]');
    $this->assertText('Country');

    $this->drupalPostForm(NULL, ['rules[new][type]' => 'sender'], 'Add rule');
    $this->assertFieldByXPath('//input[starts-with(@name, "sender")]');
    $this->assertText('Sender');

    $this->drupalPostForm(NULL, ['rules[new][type]' => 'user'], 'Add rule');
    $this->assertFieldByXPath('//input[starts-with(@name, "user")]');
    $this->assertText('SMS owner');

    // Find the rule machine names in the markup.
    preg_match_all('/\[((area|country|sender|user)_[^\]]+)]/', $this->content, $matches);
    $rule_names = array_values(array_unique($matches[1]));

    // Fill and submit the form and ensure that the ruleset is properly created.
    $ruleset_name = strtolower($this->randomMachineName());
    $edit = [
      'label' => trim($this->randomString()),
      'name' => $ruleset_name,
      'enabled' => TRUE,
      'weight' => -2,
      'gateway' => 'log',
    ];
    foreach ($rule_names as $rule_name) {
      $edit['rules[' . $rule_name . '][enabled]'] = TRUE;
      $edit['rules[' . $rule_name . '][operator]'] = 'EQ';
      $edit['rules[' . $rule_name . '][operand]'] = $this->randomString();
      if (strpos($rule_name, 'country') === 0) {
        $edit['rules[' . $rule_name . '][operand]'] = '234';
      }
      else if (strpos($rule_name, 'user') === 0) {
        $edit['rules[' . $rule_name . '][operand]'] = $user->getUsername();
      }
    }
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertResponse(200);
    $list_url = new Url('entity.sms_routing_ruleset.list');
    $this->assertUrl($list_url);

    $this->createMemoryGateway(['id' => 'memory', 'label' => 'Memory Gateway']);

    // Test that the fallback gateway link is working.
    $this->clickLink('Change fallback gateway');
    $settings_url = new Url('sms.settings', [], ['query' => ['destination' => $list_url->setAbsolute(FALSE)->toString()]]);
    $this->assertUrl($settings_url);
    $this->drupalPostForm(NULL, ['fallback_gateway' => 'memory'], 'Save configuration');

    // Verify that the redirect destination is working.
    $this->assertUrl($list_url);
    $this->assertText('Memory Gateway');
    
    // Ensure that the form fails gracefully if no fallback gateway is provided.
    $this->config('sms.settings')->set('fallback_gateway', '')->save();
    $this->drupalGet($url);
    $this->assertResponse(200);
    $this->assertText('Fallback gateway not set');
    $this->clickLink('Set fallback gateway');
    $this->assertUrl($settings_url);
    
    /** @var \Drupal\sms_rule_based\Entity\SmsRoutingRuleset $ruleset */
    $this->rulesetStorage->resetCache();
    $ruleset = $this->rulesetStorage->load($ruleset_name);
    $rules = $ruleset->getRules();
    $this->assertEqual(4, count($rules));
    $this->assertEqual('area', $rules->get($rule_names[0])->getType());
    $this->assertEqual('country', $rules->get($rule_names[1])->getType());
    $this->assertEqual('sender', $rules->get($rule_names[2])->getType());
    $this->assertEqual('user', $rules->get($rule_names[3])->getType());
  }

  /**
   * Tests full integration routing.
   *
   * Creates three gateways and 3 routing rules to route particular numbers to
   * each gateway. The asserts that they are correctly routed to the gateways.
   */
  public function testSendRoutedSms() {
    $gateway1 = $this->createMemoryGateway();
    $gateway2 = $this->createMemoryGateway();
    $gateway3 = $this->createMemoryGateway();

    $number1 = '2342342345';
    $number2 = '4564564567';
    $number3 = '987987987';

    $ruleset1 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => 'Number based ruleset',
      'weight' => -2,
      'enabled' => TRUE,
      'gateway' => $gateway1->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'number',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => $number1,
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset1->save();

    $ruleset2 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => 'Number based ruleset',
      'weight' => -2,
      'enabled' => TRUE,
      'gateway' => $gateway2->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'number',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => $number2,
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset2->save();

    $ruleset3 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => 'Number based ruleset',
      'weight' => -2,
      'enabled' => TRUE,
      'gateway' => $gateway3->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'number',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => $number3,
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset3->save();

    $sms_message = new SmsMessage('sender', [$number1, $number2, $number3], 'test message');
    $sent_messages = $this->smsProvider->send($sms_message);
    $this->assertEqual(3, count($sent_messages));
    $this->assertEqual(1, count($sent_messages[0]->getResult()->getReports()));
    $this->assertEqual(1, count($sent_messages[1]->getResult()->getReports()));
    $this->assertEqual(1, count($sent_messages[2]->getResult()->getReports()));

    // Verify correct routing. Each gateway should receive exactly one message
    // to the specific recipient.
    $messages1 = $this->getTestMessages($gateway1);
    $this->assertEqual(1, count($messages1));
    $this->assertEqual($messages1[0]->getRecipients(), [$number1]);

    $messages2 = $this->getTestMessages($gateway2);
    $this->assertEqual(1, count($messages2));
    $this->assertEqual($messages2[0]->getRecipients(), [$number2]);

    $messages3 = $this->getTestMessages($gateway3);
    $this->assertEqual(1, count($messages3));
    $this->assertEqual($messages3[0]->getRecipients(), [$number3]);

    // Add a new ruleset for messages created by user 6.
    $gateway4 = $this->createMemoryGateway();
    $ruleset4 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => 'User based ruleset',
      'weight' => -4,
      'enabled' => TRUE,
      'gateway' => $gateway4->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'user',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => '6',
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset4->save();
    // Send new message and ensure it goes to uses the gateway specified
    $this->resetTestMessages();
    $messages4 = $this->getTestMessages($gateway4);
    $this->assertEqual(0, count($messages4));
    $sms_message = new SmsMessage('sender', [$number1, $number2, $number3], 'test message', [], 6);
    $this->smsProvider->send($sms_message);
    $messages4 = $this->getTestMessages($gateway4);
    $this->assertEqual(1, count($messages4));
    $this->assertEqual($messages4[0]->getRecipients(), [$number1, $number2, $number3]);
  }

  public function testRulesetOrderWeight() {
    $gateway1 = $this->createMemoryGateway();
    $gateway2 = $this->createMemoryGateway();

    // Test that the lower weight gateway wins.
    $number = '2342342342345';
    $ruleset1 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'weight' => -2,
      'enabled' => TRUE,
      'gateway' => $gateway1->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'number',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => $number,
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset1->save();
    $ruleset2 = $this->rulesetStorage->create([
      'name' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'weight' => -1,
      'enabled' => TRUE,
      'gateway' => $gateway2->id(),
      'rules' => [
        'test_rule' => [
          'type' => 'country',
          'operator' => SmsRoutingRulePluginBase::EQ,
          'operand' => '234',
          'negated' => FALSE,
        ]
      ],
    ]);
    $ruleset2->save();
    $this->resetTestMessages();
    $sms_message = new SmsMessage('sender', [$number], 'test message', []);
    $this->smsProvider->send($sms_message);
    $this->assertEqual(1, count($this->getTestMessages($gateway1)));
    $this->assertEqual(0, count($this->getTestMessages($gateway2)));

    // Change the ruleset weight and verify that the routing has changed.
    $ruleset1->set('weight', 1)->save();
    $this->resetTestMessages();
    $sms_message = new SmsMessage('sender', [$number], 'test message', []);
    $this->smsProvider->send($sms_message);
    $this->assertEqual(0, count($this->getTestMessages($gateway1)));
    $this->assertEqual(1, count($this->getTestMessages($gateway2)));
  }

  /**
   * @todo More tests to check proper error handling and user-friendly messages.
   */

}
