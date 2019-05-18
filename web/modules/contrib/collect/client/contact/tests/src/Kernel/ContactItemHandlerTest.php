<?php

namespace Drupal\Tests\collect_client_contact\Kernel;

use Drupal\collect_client\CollectItem;
use Drupal\Component\Serialization\Json;
use Drupal\contact\Entity\Message;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the contact item handler.
 *
 * @group collect
 */
class ContactItemHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'serialization',
    'user',
    'contact',
    'collect_client',
    'collect_client_contact',
    'collect_common',
  );

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.collect_client.item_handler');
  }

  /**
   * Tests the discovery of the contact item handler.
   */
  public function testPluginDiscovery() {
    $this->assertTrue($this->pluginManager->hasDefinition('contact'), 'Found the contact plugin.');
  }

  /**
   * Tests the supports method of the contact item handler.
   */
  public function testSupports() {
    $this->installConfig(array('contact'));
    $message = Message::create(array('contact_form' => 'feedback'));
    $item = array(
      'date' => REQUEST_TIME,
      'account' => User::create(array('name' => 'Pharetra Nullam')),
      'message' => $message,
    );
    /* @var \Drupal\collect_client_contact\Plugin\collect_client\ContactItemHandler $instance */
    $instance = $this->pluginManager->createInstance('contact');
    $this->assertFalse($instance->supports(NULL), 'Do not support NULL.');
    $this->assertFalse($instance->supports('foo'), 'Do not support string.');
    $this->assertFalse($instance->supports(array()), 'Do not support array.');
    $this->assertFalse($instance->supports(42), 'Do not support integer.');
    $this->assertFalse($instance->supports($message), 'Do not support messages alone.');
    $this->assertTrue($instance->supports($item), 'Supports item with date, user and message.');
  }

  /**
   * Tests the handle method of the contact item handler.
   */
  public function testHandle() {
    $this->enableModules(array(
      'system',
      'rest',
      'hal',
    ));
    $this->installSchema('system', array('router'));
    $this->container->get('router.builder')->rebuild();
    $this->installConfig(array('contact'));

    /* @var \Drupal\collect_client_contact\Plugin\collect_client\ContactItemHandler $instance */
    $instance = $this->pluginManager->createInstance('contact');

    $user = User::create(array(
      'uid' => 42,
      'name' => 'Euismod Mollis',
      'mail' => 'euismod@example.com',
    ));
    /* @var \Drupal\contact\Entity\Message $message */
    $message = Message::create(array(
      'contact_form' => 'feedback',
      'name' => 'Aenean Inceptos',
      'mail' => 'aenean@example.com',
      'message' => 'Maecenas sed diam eget risus varius blandit sit amet non magna. Nulla vitae elit libero, a pharetra augue.',
      'recipient' => $user,
    ));
    $item = $instance->handle(array(
      'date' => REQUEST_TIME,
      'account' => $user,
      'message' => $message,
    ));
    $data = Json::decode($item->data);

    $this->assertTrue($item instanceof CollectItem, 'Handle returned a collect item.');
    $this->assertEqual('https://drupal.org/project/collect_client/contact', $item->schema_uri, 'Schema set cot contact schema uri.');
    $this->assertEqual('application/json', $item->type, 'Mimetype is application/json.');
    $this->assertFalse(empty($data['fields']), 'Got field definitions');
    $this->assertFalse(empty($data['values']), 'Got message values');
    $this->assertFalse(empty($data['user']), 'Got user values');
  }
}
