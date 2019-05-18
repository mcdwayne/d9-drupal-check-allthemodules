<?php

namespace Drupal\Tests\role_paywall\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Test setup for the Role paywall module.
 *
 * @group role_paywall
 */
class RolePaywallTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['role_paywall', 'field', 'node', 'block'];

  /**
   * The field to test as a paywall content to hide.
   *
   * @var string
   */
  protected $premiumFieldName;

  /**
   * The field to test as a paywall activator.
   *
   * @var string
   */
  protected $activateFieldName;

  /**
   * An admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Role with premium access.
   *
   * @var string
   */
  protected $adminRole;

  /**
   * Test node Title.
   *
   * @var string.
   */
  protected $testNodeTitle = 'test article title that is displayed';

  /**
   * Test node content of the premium field.
   *
   * @var string.
   */
  protected $testNodePremiumText = 'Premium content that is displayed.';

  /**
   * Test premium node object.
   *
   * @var \Drupal\Entity\NodeInterface;
   */
  protected $testNodePremium;

  /**
   * Test public node object.
   *
   * @var \Drupal\Entity\NodeInterface;
   */
  protected $testNodePublic;

  /**
   * Performs the basic setup tasks.
   */
  public function setUp() {
    parent::setUp();

    // Creates content type.
    $type = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Article',
      ]);
    $type->save();
    $this->container->get('router.builder')->rebuild();

    // Creates role and user.
    $this->adminUser = $this->drupalCreateUser(['administer blocks', 'administer site configuration', 'access administration pages', 'administer content types']);
    $admin_user_roles = $this->adminUser->getRoles();
    $this->adminRole = end($admin_user_roles);

    // Creates fields.
    $this->createFields();

    // Creates block.
    $this->container->get('module_installer')->install(['block']);
    $this->rebuildContainer();
    $this->container->get('router.builder')->rebuild();
    $this->drupalPlaceBlock('system_powered_by_block');
  }

  /**
   * Creates a test fields.
   */
  protected function createFields() {
    // Boolean field to de/activate paywall on each node.
    $this->activateFieldName = mb_strtolower($this->randomMachineName());
    $type = 'boolean';
    $widget_type = $formatter_type = $type . '_default';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->activateFieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'type' => $type,
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
      'description' => 'Is premium',
      'required' => FALSE,
    ]);
    $this->field->save();

    // Text field to hide when the content is behind the paywall.
    $this->premiumFieldName = mb_strtolower($this->randomMachineName());
    $type = 'text';
    $widget_type = $formatter_type = $type . '_default';
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $this->premiumFieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'type' => $type,
      'settings' => [],
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
      'description' => 'Premium content to hide',
      'required' => FALSE,
    ]);
    $this->field->save();

    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($this->premiumFieldName, ['region' => 'content'])->save();
    $display->setComponent($this->activateFieldName, ['region' => 'content'])->save();
  }

  /**
   * Sets a paywall configuration for article content.
   */
  protected function setConfig() {
    $this->config('role_paywall.settings')
      ->set('bundles', ['article' => 'article'])
      ->set('roles', [$this->adminRole => $this->adminRole])
      ->set('activate_paywall_field', ['article' => $this->activateFieldName])
      ->set('hidden_fields', ['article' => [$this->premiumFieldName => $this->premiumFieldName]])
      ->set('barrier_block', 'system_powered_by_block')
      ->save();
  }

  /**
   * Creates a test node.
   */
  protected function createTestNodes() {
    $config = [
      'title' => $this->testNodeTitle,
      'type' => 'article',
      $this->premiumFieldName => $this->testNodePremiumText,
    ];
    $this->testNodePublic = $this->drupalCreateNode($config + [
      $this->activateFieldName => FALSE,
    ]);
    $this->testNodePremium = $this->drupalCreateNode($config + [
      $this->activateFieldName => TRUE,
    ]);

  }

}
