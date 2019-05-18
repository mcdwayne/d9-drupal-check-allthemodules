<?php

namespace Drupal\Tests\rpn_field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * @group rpn
 */
class RpnTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'field', 'user', 'node', 'nuclear', 'rpn_field'];

  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    NodeType::create(['type' => 'bunny'])->save();
    $field_storage_config = [
      'field_name' => 'points',
      'entity_type' => 'user',
      'type' => 'integer',
    ];
    FieldStorageConfig::create($field_storage_config)->save();
  }

  public function testRpn() {
    $field_config = [
      'field_name' => 'points',
      'entity_type' => 'user',
      'bundle' => 'user',
      'default_value' => [['value' => 10]],
      'third_party_settings' => [
        'nuclear' => ['node_insert/uid' => [
          'plugin' => 'rpn',
          // foo title gets a point, bar title gets two points.
          'rpn' => '[@node_insert:title] !dup foo == !swap bar == 2 * + +',
      ]]],
    ];
    FieldConfig::create($field_config)->save();
    $account = User::create(['name' => $this->randomMachineName()]);
    $account->save();
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(10, $account->points->value);
    $values = [
      'type' => 'bunny',
      'title' => 'foo',
      'uid' => $account->id(),
    ];
    Node::create($values)->save();
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(11, $account->points->value);
    $values['title'] = 'bar';
    Node::create($values)->save();
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(13, $account->points->value);
    $values['title'] = 'baz';
    Node::create($values)->save();
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    $this->assertEquals(13, $account->points->value);
  }

}
